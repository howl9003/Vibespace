#!/bin/sh
# setup-runtime.sh — install the Archspace game content into the runtime
# filesystem layout the engine's config expects. Idempotent.
#
# Paths come from etc/archspace.config:
#   /etc/archspace/{archspace.config,script/*,banner,ip_ban,admin_list}
#   /usr/src/archspace/{form,web}
#   /var/archspace/data/{...}            (game state, persisted)
#   /var/www/localhost/htdocs/encyclopedia/*  (generated at boot)
#   /var/log/archspace                   (logs)
set -e

SRC="${ARCHSPACE_SRC:-/build/archspace}"     # the archspace_source/archspace tree
WWW=/var/www/localhost/htdocs

echo "[setup] creating runtime directories"
mkdir -p /etc/archspace/script /usr/src/archspace /var/log/archspace \
         "$WWW/encyclopedia" /var/archspace/data

echo "[setup] installing config"
cp -f "$SRC/etc/archspace.config" /etc/archspace/
# Same-origin assets: drop the dead external image server so templates emit
# /image/... (served locally by nginx from the unpacked www tier).
sed -i 's#^ImageServerURL =.*#ImageServerURL =#' /etc/archspace/archspace.config
# DB password from env (default comconq1 already in the file)
[ -n "$DB_PASS" ] && sed -i "s/^Password = .*/Password = ${DB_PASS}/" /etc/archspace/archspace.config || true
cp -f "$SRC/etc/banner"   /etc/archspace/banner   2>/dev/null || : > /etc/archspace/banner
cp -f "$SRC/etc/ip_ban"   /etc/archspace/ip_ban   2>/dev/null || : > /etc/archspace/ip_ban
[ -f /etc/archspace/admin_list ] || echo "admin@local" > /etc/archspace/admin_list

echo "[setup] installing game-content scripts (resolve .en -> extensionless)"
cp -f "$SRC"/src/script/* /etc/archspace/script/ 2>/dev/null || true
( cd /etc/archspace/script
  for f in *.en; do [ -e "$f" ] && cp -f "$f" "${f%.en}"; done )

echo "[setup] installing form + web content"
cp -rf "$SRC/src/form" /usr/src/archspace/
cp -rf "$SRC/src/web"  /usr/src/archspace/
# Same-origin images: rewrite the template token "$IMAGE_SERVER_URL/image" to
# "/image" in the *installed* page templates (the engine fills these at runtime),
# so the original separate image-server prefix collapses to this origin. The
# source tree is left untouched.
grep -rlZ '\$IMAGE_SERVER_URL/image' /usr/src/archspace/web 2>/dev/null \
    | xargs -0 -r sed -i 's#\$IMAGE_SERVER_URL/image#/image#g'

echo "[setup] creating runtime data tree (from etc/initialize_game, non-interactive)"
mkdir -p /var/archspace/data/admin /var/archspace/data/crontab \
         /var/archspace/data/event /var/archspace/data/diplomatic_message \
         /var/archspace/data/council_message
i=0; while [ "$i" -le 7 ]; do mkdir -p "/var/archspace/data/battle/$i"; i=$((i+1)); done
i=1; while [ "$i" -le 9 ]; do
  j=0; while [ "$j" -le 9 ]; do mkdir -p "/var/archspace/data/news/$i/$j"; j=$((j+1)); done
  i=$((i+1)); done

echo "[setup] creating encyclopedia output tree (engine regenerates these at boot)"
for d in race tech component ship project secret_project special_ops game ending empire_ship_design; do
  mkdir -p "$WWW/encyclopedia/$d"
done

echo "[setup] runtime layout ready"
