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
echo "==> deploy: syncing to origin/$BRANCH"
git fetch --prune origin "$BRANCH"
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"
echo "    now at: $(git rev-parse --short HEAD) $(git log -1 --pretty=%s)"

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

# --- rebuild + restart (brief blip only at the swap, not during build) -----
echo "==> deploy: building + restarting${DOMAIN:+ (HTTPS for $DOMAIN)}"
WEB_BIND="${WEB_BIND:-0.0.0.0}" WEB_PORT="${WEB_PORT:-8080}" \
  DOMAIN="${DOMAIN:-}" TLS_EMAIL="${TLS_EMAIL:-}" \
  $COMPOSE $PROFILE up --build -d

# --- reclaim space from the previous image ---------------------------------
echo "==> deploy: pruning dangling images"
$DOCKER image prune -f >/dev/null 2>&1 || true

echo "==> deploy: done"
$COMPOSE ps
