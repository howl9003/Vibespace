#!/usr/bin/env bash
# deploy.sh — pull the latest code and rebuild/restart the Archspace stack.
#
# Idempotent and safe to run repeatedly. Invoked by the GitHub Actions deploy
# workflow over SSH (.github/workflows/deploy.yml), or by hand:
#
#   bash docker/deploy/deploy.sh
#
# It reuses the deploy config the bootstrap persisted (plain HTTP vs HTTPS,
# port, domain) from docker/deploy/.deploy.env, so you don't re-specify it.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$REPO_ROOT"

# --- persisted config (written by ec2-bootstrap.sh) ------------------------
# DOMAIN, TLS_EMAIL, WEB_PORT, WEB_BIND, DEPLOY_BRANCH. All optional.
ENV_FILE="$REPO_ROOT/docker/deploy/.deploy.env"
if [ -f "$ENV_FILE" ]; then
  set -a; . "$ENV_FILE"; set +a
fi

BRANCH="${DEPLOY_BRANCH:-$(git rev-parse --abbrev-ref HEAD)}"

# What was actually deployed last time. We track this in a marker file rather
# than reading git HEAD, because the Actions workflow does its own
# `git reset --hard` BEFORE invoking this script -- so by now HEAD is already
# the new commit and a HEAD-vs-HEAD comparison would never detect a change
# (which silently turned every engine deploy into a no-rebuild restart).
MARKER="$REPO_ROOT/docker/deploy/.last_deployed"
OLD_REV="$(cat "$MARKER" 2>/dev/null || echo '')"

# --- sync the working tree to the remote branch ----------------------------
echo "==> deploy: syncing to origin/$BRANCH"
git fetch --prune origin "$BRANCH"
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"
NEW_REV="$(git rev-parse HEAD)"
echo "    last deployed: ${OLD_REV:-<none>}  ->  now: $(git rev-parse --short HEAD) $(git log -1 --pretty=%s)"

# --- decide: cheap restart vs full rebuild ---------------------------------
# The compose file bind-mounts the web tier, page templates, nginx config and
# setup scripts; the entrypoint re-assembles them on every start. So UI /
# template / config changes apply with a plain `restart` (seconds). A real image
# rebuild (minutes) is needed for anything BAKED INTO the image: the compiled C++
# engine, the CGI adapter, the Dockerfile, the www-new asset tree, the www
# tarball, and the src/script game-data tables. FORCE_REBUILD=1 always rebuilds.
NEEDS_BUILD=0
if [ "${FORCE_REBUILD:-0}" = "1" ] || [ -z "$OLD_REV" ]; then
  # Explicit override, or no record of a prior deploy -> build to be safe.
  NEEDS_BUILD=1
elif [ "$OLD_REV" != "$NEW_REV" ] && git diff --name-only "$OLD_REV" "$NEW_REV" \
       | grep -qE '^archspace_source/archspace/src/(libs|apps|script)/|^archspace_source/archspace/www-new/|^archspace_source/archspace\.tar\.gz$|^docker/as-cgi/|^docker/Dockerfile$'; then
  NEEDS_BUILD=1
fi

# --- docker (fall back to sudo only if the daemon isn't reachable) ----------
DOCKER="docker"
if ! $DOCKER info >/dev/null 2>&1; then
  DOCKER="sudo docker"
fi
COMPOSE="$DOCKER compose -f docker/docker-compose.yml"

PROFILE=""
if [ -n "${DOMAIN:-}" ]; then
  PROFILE="--profile https"
fi

env_prefix() {
  WEB_BIND="${WEB_BIND:-0.0.0.0}" WEB_PORT="${WEB_PORT:-8080}" \
    DOMAIN="${DOMAIN:-}" TLS_EMAIL="${TLS_EMAIL:-}" "$@"
}

if [ "$NEEDS_BUILD" = "1" ]; then
  echo "==> deploy: engine/Dockerfile changed -> rebuilding image${DOMAIN:+ (HTTPS for $DOMAIN)}"
  env_prefix $COMPOSE $PROFILE up --build -d
  echo "==> deploy: pruning dangling images"
  $DOCKER image prune -f >/dev/null 2>&1 || true
else
  echo "==> deploy: web/template/config only -> restart (no rebuild)"
  # `up -d` applies any compose/volume changes; `restart` re-runs the entrypoint
  # so setup-web.sh / setup-runtime.sh re-assemble from the updated sources.
  env_prefix $COMPOSE $PROFILE up -d
  $COMPOSE restart archspace
fi

# Record what we just deployed so the next run can diff against it.
echo "$NEW_REV" > "$MARKER"

# --- notify: email a deploy summary (what was built + what changed) ---------
# Reuses the stack's SMTP_* convention (the same vars the app uses for
# password-reset mail). Configure these in docker/deploy/.deploy.env (or export
# them in the runner environment):
#   NOTIFY_EMAIL  recipient address                         -- required to send
#   SMTP_HOST     SMTP server (e.g. email-smtp.us-east-1.amazonaws.com / SES)
#   SMTP_PORT     587 (STARTTLS, default) or 465 (implicit TLS)
#   SMTP_USER     SMTP username
#   SMTP_PASS     SMTP password
#   SMTP_FROM     From/envelope address (default: SMTP_USER)
# If any of NOTIFY_EMAIL/SMTP_HOST/SMTP_USER/SMTP_PASS is unset, the summary is
# just logged (never an error), mirroring the app's "no SMTP -> reset links are
# logged" behaviour. A mail failure warns but never fails the deploy.
notify_deploy() {
  local action="$1"
  local short subject range_desc changed subj body from port url msg

  short="$(git rev-parse --short HEAD)"
  subject="$(git log -1 --pretty=%s 2>/dev/null || echo '')"

  if [ -n "$OLD_REV" ] && [ "$OLD_REV" != "$NEW_REV" ]; then
    range_desc="$(git log --oneline "$OLD_REV..$NEW_REV" 2>/dev/null | head -50)"
    changed="$(git diff --name-only "$OLD_REV" "$NEW_REV" 2>/dev/null | head -100)"
  else
    range_desc="$(git log --oneline -5 "$NEW_REV" 2>/dev/null)"
    changed="$([ -n "$OLD_REV" ] && echo '(no file changes)' || echo '(initial deploy)')"
  fi

  subj="[Archspace deploy] ${action} ${short} - ${subject}"
  body="$(cat <<EOF
Archspace deploy completed.

When:    $(date -u '+%Y-%m-%d %H:%M:%S UTC')
Host:    $(hostname 2>/dev/null || echo '?')
Branch:  ${BRANCH}
Action:  ${action}  ($([ "$action" = REBUILD ] && echo 'image rebuilt - engine/CGI/Dockerfile change' || echo 'restart only - web/template/config'))
Commit:  ${short}  ${subject}
Range:   ${OLD_REV:-<none>} -> ${NEW_REV}

Commits in this deploy:
${range_desc:-<none>}

Files changed:
${changed:-<none>}

Containers:
$($COMPOSE ps 2>/dev/null | head -20)
EOF
)"

  if [ -z "${NOTIFY_EMAIL:-}" ] || [ -z "${SMTP_HOST:-}" ] || [ -z "${SMTP_USER:-}" ] || [ -z "${SMTP_PASS:-}" ]; then
    echo "==> deploy: NOTIFY_EMAIL/SMTP_* not fully set -> logging summary instead of emailing"
    printf '%s\n%s\n' "Subject: $subj" "$body"
    return 0
  fi

  port="${SMTP_PORT:-587}"
  from="${SMTP_FROM:-$SMTP_USER}"
  if [ "$port" = "465" ]; then url="smtps://${SMTP_HOST}:${port}"; else url="smtp://${SMTP_HOST}:${port}"; fi

  # RFC822 message (CRLF line endings) piped to curl's SMTP client over stdin.
  msg="$(printf 'From: %s\r\nTo: %s\r\nSubject: %s\r\nDate: %s\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n' \
           "$from" "$NOTIFY_EMAIL" "$subj" "$(date -R 2>/dev/null || date)")"
  msg="$msg$(printf '%s' "$body" | sed 's/$/\r/')"

  if printf '%s\r\n' "$msg" | curl --silent --show-error --ssl-reqd \
        --url "$url" --user "${SMTP_USER}:${SMTP_PASS}" \
        --mail-from "$from" --mail-rcpt "$NOTIFY_EMAIL" --upload-file - ; then
    echo "==> deploy: emailed deploy summary to ${NOTIFY_EMAIL}"
  else
    echo "==> deploy: WARNING: deploy email failed (continuing)"
  fi
  return 0
}

ACTION=RESTART; [ "$NEEDS_BUILD" = "1" ] && ACTION=REBUILD
notify_deploy "$ACTION" || true

echo "==> deploy: done"
$COMPOSE ps
