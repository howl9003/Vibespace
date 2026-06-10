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

# 1a2) Planet thumbnails. The engine emits /image/as_game/planets/<Type>_<Size>.gif
#      on the domestic / planet-management pages, but the original www tier never
#      shipped that directory -- the icons only exist under www-new. Overlay just
#      that one asset dir so the planet icons resolve.
SRC_TREE="${ARCHSPACE_SRC:-/build/archspace}"
PLANETS="$SRC_TREE/www-new/image/as_game/planets"
if [ -d "$PLANETS" ]; then
    echo "[web] adding planet thumbnails (from www-new)"
    mkdir -p "$WWW/image/as_game/planets"
    cp -rf "$PLANETS"/. "$WWW/image/as_game/planets"/ 2>/dev/null || true
    find "$WWW/image/as_game/planets" -type d -name CVS -prune -exec rm -rf {} + 2>/dev/null || true
fi

# 1b) De-framed shell + other web overrides (replace the obsolete <frameset>
#     with a CSS-grid + named-iframe shell; same look, modern + mobile).
OVERRIDES="${WEB_OVERRIDES:-/build/docker/web-overrides}"
if [ -d "$OVERRIDES" ]; then
    echo "[web] applying web overrides (de-framed shell, ...)"
    cp -rf "$OVERRIDES"/. "$WWW"/
fi

# 1c) Modern-browser compat on the static www pages (UTF-8, cursor:pointer).
grep -rlZ -iE 'charset=euc-kr|charset=iso-8859-1' "$WWW" 2>/dev/null \
    | xargs -0 -r sed -i -E 's/charset=euc-kr/charset=utf-8/Ig; s/charset=iso-8859-1/charset=utf-8/Ig'
grep -rlZ 'cursor:hand' "$WWW" 2>/dev/null \
    | xargs -0 -r sed -i 's/cursor:hand/cursor:pointer/g'

# 1d) UI font -> Times New Roman across the whole assembled web root.
#     archspace.css (linked by every in-game page) drives most of the UI, but
#     the original templates also hard-code <font face="Arial,..."> and inline
#     font-family styles, so normalize those too. Each rule is bounded by its
#     value delimiter (; } or the closing quote) so a match can't run past the
#     value, and the replacement contains no "Arial" -> re-runs are no-ops.
echo "[web] setting UI font to Times New Roman"
# CSS files: font-family values (delimited by ; or })
grep -rlZ -i --include='*.css' arial "$WWW" 2>/dev/null \
    | xargs -0 -r sed -i -E 's/font-family:[^;}]*Arial[^;}]*/font-family: "Times New Roman", "Times", serif/gI'
# HTML/PHP templates: <font face="...Arial..."> and inline style font-family
grep -rlZ -i --include='*.html' --include='*.htm' --include='*.phtml' --include='*.php' arial "$WWW" 2>/dev/null \
    | xargs -0 -r sed -i -E \
        -e 's/(face=")[^"]*Arial[^"]*(")/\1Times New Roman, Times, serif\2/gI' \
        -e 's/font-family:[^;"}]*Arial[^;"}]*/font-family:Times New Roman, Times, serif/gI'

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
# The tarball ships the legacy 2004 portal frameset as the root index.html
# (frames up.html / left.html / main.php — a frontend we've dropped). Remove it
# so nginx serves our index.php redirect at "/", and drop the two frame docs it
# pulled in (only meaningful inside that frameset; they reference $ad_line and
# portal-era images).
rm -f "$WWW/index.html" "$WWW/index.htm" "$WWW/up.html" "$WWW/left.html"

# Neutralize the remaining portal-dependent entry points so a direct hit can't
# throw a PHP fatal (they require the unshipped IPBSDK / the dropped portal
# socket). main.php -> the proper landing; the legacy logouts -> modern logout.
cat > "$WWW/main.php" <<'PHP'
<?php header('Location: /'); exit; ?>
PHP
cat > "$WWW/logout.phtml" <<'PHP'
<?php header('Location: /auth/logout.php'); exit; ?>
PHP
cat > "$WWW/game_logout.phtml" <<'PHP'
<?php header('Location: /auth/logout.php'); exit; ?>
PHP
# A friendly root: unauthenticated -> login; logged-in with a character -> the
# de-framed game shell; logged-in without one -> the standalone create page.
cat > "$WWW/index.php" <<'PHP'
<?php
require __DIR__ . '/auth/lib.php';
$acct = function_exists('current_account') ? current_account() : null;
header('Location: ' . ($acct ? game_entry_url((int)$acct['id']) : '/auth/login.php'));
exit;
PHP

echo "[web] web root assembled at $WWW"
