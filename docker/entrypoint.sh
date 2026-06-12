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

# --- 2b. lightweight schema migrations (idempotent, every boot) ------------
# Existing DBs don't re-run all.sql, so widen columns here. Guarded by the
# current type so we don't rebuild the table on every restart. Fresh DBs are
# already created at the new width and skip the ALTER.
PLAYER_NAME_TYPE=$($M -N -B "$DB_NAME" -e "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$DB_NAME' AND TABLE_NAME='player' AND COLUMN_NAME='name';" 2>/dev/null)
if [ -n "$PLAYER_NAME_TYPE" ] && [ "$PLAYER_NAME_TYPE" != "char(40)" ]; then
    log "migrating: player.name $PLAYER_NAME_TYPE -> char(40)"
    $M "$DB_NAME" -e "ALTER TABLE player MODIFY name char(40) NOT NULL;" || true
fi

# Attack templates (saved offence deployments). Additive only -- existing DBs
# never re-run all.sql, so create these if absent. Mirrors plan/defense_fleet
# but kept in their own tables so they never leak into defender plan scans.
$M "$DB_NAME" -e "CREATE TABLE IF NOT EXISTS attack_plan (
    owner int NOT NULL, id int NOT NULL,
    name char(40) NOT NULL, capital int NOT NULL,
    PRIMARY KEY(owner, id));" || true
$M "$DB_NAME" -e "CREATE TABLE IF NOT EXISTS attack_fleet (
    owner int NOT NULL, plan_id int NOT NULL, fleet_id int NOT NULL,
    command int NOT NULL, x int NOT NULL, y int NOT NULL,
    PRIMARY KEY(owner, plan_id, fleet_id));" || true

# battle_record.result_report: both-sides manifest + ships killed, captured at
# battle time so the report-detail page can show it. Additive trailing column;
# existing DBs never re-run all.sql, so add it if absent. It MUST be the last
# column (the engine reads battle_record via SELECT * by positional index, and
# STORE_RESULT_REPORT is the final index). ADD COLUMN IF NOT EXISTS is idempotent.
$M "$DB_NAME" -e "ALTER TABLE battle_record ADD COLUMN IF NOT EXISTS result_report text;" || true

# --- 3. runtime layout ------------------------------------------------------
# Invoke via `sh` (not as an executable) so these still run when the scripts are
# bind-mounted from a host checkout that didn't preserve the +x bit.
log "installing runtime layout + game content"
ARCHSPACE_SRC="$SRC" sh /usr/local/bin/setup-runtime.sh

# --- 4. web tier (assemble web root, php-fpm, fcgiwrap, nginx) --------------
log "assembling web root"
ARCHSPACE_TARBALL=/build/archspace.tar.gz AUTH_SRC=/build/web/auth \
    sh /usr/local/bin/setup-web.sh || true

# Enable our nginx site
if [ -f /etc/nginx/sites-available/archspace ]; then
    ln -sfn /etc/nginx/sites-available/archspace /etc/nginx/sites-enabled/default
fi

# php-fpm: pass DB_* env to workers (clear_env=no) and expose a stable socket.
PHP_FPM_BIN="$(command -v php-fpm8.3 || command -v php-fpm || true)"
PHP_POOL="$(ls /etc/php/*/fpm/pool.d/www.conf 2>/dev/null | head -1)"
if [ -n "$PHP_POOL" ]; then
    sed -i 's#^;\?listen = .*#listen = /run/php/php-fpm.sock#' "$PHP_POOL"
    sed -i 's#^;\?clear_env = .*#clear_env = no#' "$PHP_POOL"
    grep -q '^clear_env' "$PHP_POOL" || echo 'clear_env = no' >> "$PHP_POOL"
    # The SSE push bridge (events.php) holds one worker per connected player for
    # up to ~55s, so give the pool plenty of headroom beyond the default 5.
    PHP_FPM_MAX_CHILDREN="${PHP_FPM_MAX_CHILDREN:-50}"
    sed -i "s#^;\?pm.max_children = .*#pm.max_children = ${PHP_FPM_MAX_CHILDREN}#" "$PHP_POOL"
    sed -i 's#^;\?pm = .*#pm = dynamic#' "$PHP_POOL"
    sed -i 's#^;\?pm.start_servers = .*#pm.start_servers = 8#' "$PHP_POOL"
    sed -i 's#^;\?pm.min_spare_servers = .*#pm.min_spare_servers = 4#' "$PHP_POOL"
    sed -i 's#^;\?pm.max_spare_servers = .*#pm.max_spare_servers = 16#' "$PHP_POOL"
fi
mkdir -p /run/php
export DB_HOST="${DB_HOST:-127.0.0.1}" DB_USER="${DB_USER:-root}" \
       DB_PASS="${DB_PASS:-comconq1}" DB_NAME="${DB_NAME:-Archspace}" \
       SMTP_HOST="${SMTP_HOST:-}" SMTP_PORT="${SMTP_PORT:-}" \
       SMTP_USER="${SMTP_USER:-}" SMTP_PASS="${SMTP_PASS:-}" SMTP_FROM="${SMTP_FROM:-}"
[ -n "$PHP_FPM_BIN" ] && { log "starting php-fpm"; "$PHP_FPM_BIN" -D 2>/dev/null || true; }

# fcgiwrap: runs the as-cgi adapter for *.as requests
if command -v fcgiwrap >/dev/null 2>&1 && [ -x /usr/local/bin/as-cgi ]; then
    log "starting fcgiwrap (as-cgi adapter)"
    rm -f /run/fcgiwrap.sock
    fcgiwrap -s unix:/run/fcgiwrap.sock >/var/log/archspace/fcgiwrap.log 2>&1 &
    sleep 1; chmod 666 /run/fcgiwrap.sock 2>/dev/null || true
fi

log "starting nginx"
nginx 2>/dev/null || nginx -g 'daemon on;' 2>/dev/null || true

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
