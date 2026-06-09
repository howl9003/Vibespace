#!/usr/bin/env bash
# ec2-bootstrap.sh — set up Docker and launch Archspace on a fresh
# Ubuntu 24.04 EC2 instance. Run from the repo root:
#
#   sudo bash docker/deploy/ec2-bootstrap.sh
#
# Optional env:
#   WEB_PORT=80   (default 8080)  host port to expose the game on
set -euo pipefail

WEB_PORT="${WEB_PORT:-8080}"
REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$REPO_ROOT"

echo "==> Archspace EC2 bootstrap (repo: $REPO_ROOT, port: $WEB_PORT)"

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
WEB_PORT="$WEB_PORT" docker compose -f docker/docker-compose.yml up --build -d

echo
echo "==> done. The game is starting up."
PUBIP="$(curl -fsS --max-time 3 http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || echo '<server-ip>')"
echo "    Open:   http://${PUBIP}:${WEB_PORT}/"
echo "    Logs:   docker compose -f docker/docker-compose.yml logs -f"
echo "    Health: docker compose -f docker/docker-compose.yml ps"
echo
echo "    Make sure your EC2 Security Group allows inbound TCP ${WEB_PORT}."
