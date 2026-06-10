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

# --- sync the working tree to the remote branch ----------------------------
OLD_REV="$(git rev-parse HEAD 2>/dev/null || echo '')"
echo "==> deploy: syncing to origin/$BRANCH"
git fetch --prune origin "$BRANCH"
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"
NEW_REV="$(git rev-parse HEAD)"
echo "    now at: $(git rev-parse --short HEAD) $(git log -1 --pretty=%s)"

# --- decide: cheap restart vs full rebuild ---------------------------------
# The compose file bind-mounts the web tier, page templates, nginx config and
# setup scripts; the entrypoint re-assembles them on every start. So UI /
# template / config changes apply with a plain `restart` (seconds). Only a
# change to the compiled C++ engine, the CGI adapter, or the Dockerfile needs a
# real image rebuild (minutes). FORCE_REBUILD=1 overrides to always rebuild.
NEEDS_BUILD=0
if [ "${FORCE_REBUILD:-0}" = "1" ] || [ -z "$OLD_REV" ]; then
  # Explicit override, or first deploy on this checkout -> build to be safe.
  NEEDS_BUILD=1
elif [ "$OLD_REV" != "$NEW_REV" ] && git diff --name-only "$OLD_REV" "$NEW_REV" \
       | grep -qE '^archspace_source/archspace/src/(libs|apps)/|^docker/as-cgi/|^docker/Dockerfile$'; then
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

echo "==> deploy: done"
$COMPOSE ps
