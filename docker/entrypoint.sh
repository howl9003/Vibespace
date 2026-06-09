#!/bin/bash
# entrypoint.sh — bring up MariaDB, initialize the Archspace DB on first boot,
# install the runtime layout, and start the game server (+ web tier).
set -e

DB_NAME="${DB_NAME:-Archspace}"
DB_PASS="${DB_PASS:-comconq1}"
SRC="${ARCHSPACE_SRC:-/build/archspace}"

log(){ echo "[entrypoint] $*"; }

# --- 1. MariaDB -------------------------------------------------------------
mkdir -p /run/mysqld && chown -R mysql:mysql /run/mysqld /var/lib/mysql
FIRST_BOOT=0
if [ ! -d /var/lib/mysql/mysql ]; then
    log "initializing MariaDB data dir"
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql --auth-root-authentication-method=normal >/dev/null
    FIRST_BOOT=1
fi

log "starting MariaDB"
mariadbd --user=mysql >/var/log/archspace_mariadb.log 2>&1 &
MARIADB_PID=$!
for i in $(seq 1 30); do
    mariadb -uroot -e "SELECT 1" >/dev/null 2>&1 && break
    mariadb -uroot -p"$DB_PASS" -e "SELECT 1" >/dev/null 2>&1 && break
    sleep 1
done

# --- 2. DB init (first boot only) ------------------------------------------
# Pick a working admin invocation (password may already be set on a reused volume)
if mariadb -uroot -e "SELECT 1" >/dev/null 2>&1; then M="mariadb -uroot"; else M="mariadb -uroot -p$DB_PASS"; fi

if [ "$FIRST_BOOT" = "1" ] || ! $M -e "USE $DB_NAME" 2>/dev/null; then
    log "creating database + loading schema"
    $M -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET latin1;"
    $M "$DB_NAME" < "$SRC/src/apps/archspace/DB/all.sql"
    # modern auth tables (new accounts/sessions store; replaces legacy portal)
    [ -f /build/web/auth/schema.sql ] && $M "$DB_NAME" < /build/web/auth/schema.sql || true
    log "setting root password"
    $M -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '$DB_PASS'; FLUSH PRIVILEGES;"
fi
# ensure non-strict mode for the live connection too
mariadb -uroot -p"$DB_PASS" -e "SET GLOBAL sql_mode='NO_ENGINE_SUBSTITUTION';" 2>/dev/null || true

# --- 3. runtime layout ------------------------------------------------------
log "installing runtime layout + game content"
ARCHSPACE_SRC="$SRC" /usr/local/bin/setup-runtime.sh

# --- 4. web tier (php-fpm + nginx), if present ------------------------------
if [ -d /build/web ]; then
    log "installing web tier"
    mkdir -p /var/www/localhost/htdocs
    cp -rf /build/web/* /var/www/localhost/htdocs/ 2>/dev/null || true
    [ -f /etc/nginx/sites-available/archspace ] && ln -sfn /etc/nginx/sites-available/archspace /etc/nginx/sites-enabled/default
    service php8.3-fpm start 2>/dev/null || (mkdir -p /run/php && php-fpm8.3 -D 2>/dev/null) || true
    nginx 2>/dev/null || true
fi

# --- 5. game server ---------------------------------------------------------
log "starting Archspace game server (listens on 12350, localhost)"
trap 'kill -TERM "$GAME_PID" "$MARIADB_PID" 2>/dev/null; wait' TERM INT
/usr/sbin/archspace &
GAME_PID=$!

# wait for the game to listen, then report
for i in $(seq 1 20); do
    if ss -ltn 2>/dev/null | grep -q ':12350'; then log "game server is LISTENING on 12350"; break; fi
    sleep 1
done

wait "$GAME_PID"
