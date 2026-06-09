#!/bin/sh
# setup-web.sh — assemble the web root: the original `www` tier (unpacked from
# archspace.tar.gz) + the modern auth service, with login/register repointed at
# the auth service. Idempotent.
set -e

WWW=/var/www/localhost/htdocs
TARBALL="${ARCHSPACE_TARBALL:-/build/archspace.tar.gz}"
AUTH_SRC="${AUTH_SRC:-/build/web/auth}"

mkdir -p "$WWW"

# 1) Original www tier (images, encyclopedia, css/js, frames, static pages).
if [ -f "$TARBALL" ]; then
    echo "[web] unpacking original www tier"
    tmp="$(mktemp -d)"
    tar xzf "$TARBALL" -C "$tmp" 'archspace/www' 2>/dev/null || tar xzf "$TARBALL" -C "$tmp" 2>/dev/null
    cp -rf "$tmp"/archspace/www/. "$WWW"/ 2>/dev/null || true
    rm -rf "$tmp"
    # strip CVS dirs that ship inside the tarball
    find "$WWW" -type d -name CVS -prune -exec rm -rf {} + 2>/dev/null || true
fi

# 2) Modern auth service at /auth/
if [ -d "$AUTH_SRC" ]; then
    echo "[web] installing auth service at /auth/"
    mkdir -p "$WWW/auth"
    cp -f "$AUTH_SRC"/*.php "$AUTH_SRC"/*.md "$WWW/auth/" 2>/dev/null || true
fi

# 3) Repoint the legacy login/register pages at the modern auth service.
echo "[web] repointing login/register at the auth service"
cat > "$WWW/login.php"    <<'PHP'
<?php header('Location: /auth/login.php'); exit; ?>
PHP
cat > "$WWW/register.php" <<'PHP'
<?php header('Location: /auth/register.php'); exit; ?>
PHP
# A friendly root: send unauthenticated visitors to login, else into the game frame.
cat > "$WWW/index.php" <<'PHP'
<?php
require __DIR__ . '/auth/lib.php';
$acct = function_exists('current_account') ? current_account() : null;
header('Location: ' . ($acct ? '/main.php' : '/auth/login.php'));
exit;
PHP

echo "[web] web root assembled at $WWW"
