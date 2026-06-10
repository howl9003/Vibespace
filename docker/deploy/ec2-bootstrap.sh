#!/usr/bin/env bash
# ec2-bootstrap.sh — set up Docker and launch Archspace on a fresh
# Ubuntu 24.04 EC2 instance. Run from the repo root:
#
#   sudo bash docker/deploy/ec2-bootstrap.sh
#
# Optional env:
#   WEB_PORT=80   (default 8080)  host port to expose the game on (plain HTTP)
#   DOMAIN=play.example.com       enable automatic HTTPS via Caddy (needs a DNS
#                                 record pointing here + inbound 80/443 open)
#   TLS_EMAIL=you@example.com     optional ACME account email for HTTPS
set -euo pipefail

WEB_PORT="${WEB_PORT:-8080}"
DOMAIN="${DOMAIN:-}"
TLS_EMAIL="${TLS_EMAIL:-}"
REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$REPO_ROOT"

if [ -n "$DOMAIN" ]; then
  echo "==> Archspace EC2 bootstrap (repo: $REPO_ROOT, HTTPS for: $DOMAIN)"
else
  echo "==> Archspace EC2 bootstrap (repo: $REPO_ROOT, plain HTTP port: $WEB_PORT)"
fi

# --- 1. Docker engine + compose plugin -------------------------------------
if ! command -v docker >/dev/null 2>&1; then
  echo "==> installing Docker"
  apt-get update -y
  apt-get install -y ca-certificates curl gnupg
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
    | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  chmod a+r /etc/apt/keyrings/docker.gpg
  . /etc/os-release
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
    > /etc/apt/sources.list.d/docker.list
  apt-get update -y
  apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
  systemctl enable --now docker
fi
docker --version
docker compose version

# --- 2. Swap (the C++ compile is RAM-hungry; help small instances) ---------
MEM_KB=$(awk '/MemTotal/{print $2}' /proc/meminfo)
if [ "$MEM_KB" -lt 3500000 ] && [ ! -f /swapfile ]; then
  echo "==> adding 2G swap (RAM < ~3.5G) so the build doesn't OOM"
  fallocate -l 2G /swapfile || dd if=/dev/zero of=/swapfile bs=1M count=2048
  chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

# --- 3. Build + run --------------------------------------------------------
echo "==> building + starting the stack (first build compiles the engine, ~2-5 min)"
if [ -n "$DOMAIN" ]; then
  # HTTPS: Caddy fronts the app on 80/443; keep the app's own port on localhost.
  WEB_BIND=127.0.0.1 WEB_PORT="$WEB_PORT" DOMAIN="$DOMAIN" TLS_EMAIL="$TLS_EMAIL" \
    docker compose -f docker/docker-compose.yml --profile https up --build -d
else
  WEB_PORT="$WEB_PORT" docker compose -f docker/docker-compose.yml up --build -d
fi

echo
echo "==> done. The game is starting up."
PUBIP="$(curl -fsS --max-time 3 http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || echo '<server-ip>')"
if [ -n "$DOMAIN" ]; then
  echo "    Open:   https://${DOMAIN}/   (Caddy fetches a cert on first request — give it a few seconds)"
  echo "    DNS:    make sure ${DOMAIN} has an A record -> ${PUBIP}"
  echo "    Ports:  open inbound 80 AND 443 in the EC2 Security Group."
else
  echo "    Open:   http://${PUBIP}:${WEB_PORT}/"
  echo "    Ports:  open inbound TCP ${WEB_PORT} in the EC2 Security Group."
fi
echo "    Logs:   docker compose -f docker/docker-compose.yml logs -f"
echo "    Health: docker compose -f docker/docker-compose.yml ps"
