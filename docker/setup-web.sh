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
    # No-clobber: never overwrite a good tarball image with a www-new one (see
    # the encyclopedia note below). The tarball ships no planet thumbnails, so
    # www-new fills them regardless. NOTE: the www-new planet GIFs are currently
    # byte-corrupted with no good source -- tracked for reconstruction.
    cp -rn "$PLANETS"/. "$WWW/image/as_game/planets"/ 2>/dev/null || true
    find "$WWW/image/as_game/planets" -type d -name CVS -prune -exec rm -rf {} + 2>/dev/null || true
fi

# 1a3) Encyclopedia icons. The original www tarball (unpacked in step 1 above)
#      DOES ship valid encyclopedia images under image/as_login/encyclopedia/.
#      The copies under www-new/ are byte-corrupted (a Windows/CVS checkout
#      mangled the binary GIFs -- 277 of 292 fail to decode), so overlaying them
#      with `cp -f` CLOBBERED the good tarball images and the encyclopedia
#      rendered broken. Use NO-CLOBBER (`cp -n`): keep the tarball's good images
#      and let www-new only fill genuine gaps (a few special_ops/* icons the
#      tarball lacks). The source tree's corrupt files are left untouched.
ENCYC="$SRC_TREE/www-new/image/as_login/encyclopedia"
ENCYC_DEST="$WWW/image/as_login/encyclopedia"
if [ -d "$ENCYC" ]; then
    echo "[web] filling encyclopedia icon gaps from www-new (no-clobber; tarball wins)"
    mkdir -p "$ENCYC_DEST"
    cp -rn "$ENCYC"/. "$ENCYC_DEST"/ 2>/dev/null || true
    find "$ENCYC_DEST" -type d -name CVS -prune -exec rm -rf {} + 2>/dev/null || true
    # Sentinel: the race banner ships in the tarball; if it's absent the web
    # tier wasn't assembled -- surface it instead of a silent 404.
    if [ -f "$ENCYC_DEST/race/race_img.gif" ]; then
        echo "[web] encyclopedia icons OK ($(find "$ENCYC_DEST" -type f | wc -l) files)"
    else
        echo "[web] ERROR: encyclopedia images missing after assembly (dest=$ENCYC_DEST)" >&2
    fi
fi
# NOTE: the static encyclopedia *HTML* under $WWW/encyclopedia is NOT placed here
# -- the game engine regenerates it during load(), which runs AFTER this script.
# Those pages emit a literal $IMAGE_SERVER_URL token that must be blanked for the
# images to resolve same-origin; that blanking lives in entrypoint.sh AFTER the engine
# has generated the pages (a sed here would be overwritten on every boot). See the
# "static encyclopedia: same-origin image paths" step in docker/entrypoint.sh.

# 1a4) Fleet marker icons. The HTML5 battle-deployment board (as-deploy.js) draws
#      the original ship markers (ship_set.gif / ship_cap.gif from
#      /image/as_game/fleet/). No-clobber overlay (tarball wins) -- the tarball
#      ships good versions; www-new only fills any gaps.
FLEETIMG="$SRC_TREE/www-new/image/as_game/fleet"
if [ -d "$FLEETIMG" ]; then
    echo "[web] filling fleet marker icon gaps from www-new (no-clobber; tarball wins)"
    mkdir -p "$WWW/image/as_game/fleet"
    cp -rn "$FLEETIMG"/. "$WWW/image/as_game/fleet"/ 2>/dev/null || true
    find "$WWW/image/as_game/fleet" -type d -name CVS -prune -exec rm -rf {} + 2>/dev/null || true
fi

# 1a5) Trabotulin (CVS-merge 11th race) art. A brand-new race, so NONE of its
#      images ship in the tarball -- they exist only under www-new. Overlay the
#      specific Trabotulin assets (the create-screen tile, the in-game race art,
#      and the info icon) so the create screen and the race dashboard resolve.
#      No-clobber (`cp -n`, tarball wins), consistent with the overlays above --
#      these are new files, so nothing is ever overwritten.
TRAB="$SRC_TREE/www-new/image"
if [ -d "$TRAB/as_game/race/trabotulin" ]; then
    echo "[web] adding Trabotulin race art (from www-new)"
    mkdir -p "$WWW/image/as_game/race/trabotulin" \
             "$WWW/image/as_login/create_character" \
             "$WWW/image/as_game/info"
    cp -rn "$TRAB/as_game/race/trabotulin"/. "$WWW/image/as_game/race/trabotulin"/ 2>/dev/null || true
    cp -n  "$TRAB/as_login/create_character/create_trabotulin.gif" "$WWW/image/as_login/create_character"/ 2>/dev/null || true
    cp -n  "$TRAB/as_game/info/symbol_trabotulin.gif"              "$WWW/image/as_game/info"/ 2>/dev/null || true
    find "$WWW/image/as_game/race/trabotulin" -type d -name CVS -prune -exec rm -rf {} + 2>/dev/null || true
fi

# 1a6) Black market placeholder icons. The office no-item / error templates
#      (rare_goods, leasers_office, tech_deck, fleet_deck, officers_lounge) all
#      reference image/as_game/black_market/black_market_error.gif, which the
#      original www tarball never shipped -- so an office with nothing for sale
#      (e.g. Rare Goods Office with no projects, Leaser's Office with no planets)
#      rendered a broken image. That asset exists only under www-new, and unlike
#      the dirs above the black_market dir was never overlaid, so it 404'd.
#      Overlay it (no-clobber, tarball wins) so the placeholder resolves.
BLACKMARKET="$SRC_TREE/www-new/image/as_game/black_market"
if [ -d "$BLACKMARKET" ]; then
    echo "[web] filling black_market image gaps from www-new (no-clobber; tarball wins)"
    mkdir -p "$WWW/image/as_game/black_market"
    cp -rn "$BLACKMARKET"/. "$WWW/image/as_game/black_market"/ 2>/dev/null || true
    find "$WWW/image/as_game/black_market" -type d -name CVS -prune -exec rm -rf {} + 2>/dev/null || true
fi

# 1a7) Replace the legacy Korean "under construction" placeholder with a clean
#      English one. The original www tier shipped a Korean "no image / 이미지
#      작업중" placeholder for 35 unfinished art slots: the black market, the 17
#      diplomacy spy-op icons (image/as_game/diplomacy/spy_*.gif), the 13
#      fleet-action buttons (image/as_game/fleet/*.gif), and ending_score /
#      retire / event/meeting / result/new_player_assign. Replace every SERVED
#      copy -- matched by CONTENT HASH so it catches tarball- and www-new-sourced
#      copies alike -- with a neutral English placeholder. (black_market_error.gif
#      is overlaid above with its own "No items available" art, a different hash,
#      so it is left untouched here.)
PLACEHOLDER_EN="$SRC_TREE/www-new/image/placeholder_en.gif"
PH_SHA="915a50c7802b733d88b2ef40d7ed13c7edfe4290419706c5f9ec2b4edcca377f"
if [ -f "$PLACEHOLDER_EN" ]; then
    cnt=$(find "$WWW/image" -type f -name '*.gif' -exec sh -c '
        en="$1"; sha="$2"; shift 2
        for f; do
            [ "$(sha256sum "$f" | cut -d" " -f1)" = "$sha" ] && cp -f "$en" "$f" && echo x
        done
    ' sh "$PLACEHOLDER_EN" "$PH_SHA" {} + 2>/dev/null | wc -l)
    echo "[web] replaced $cnt legacy Korean placeholder image(s) with English version"
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

# 1e) Mobile reflow for the legacy in-game pages. Every in-game template links
#     /archspace.css, so appending ONE media query here makes all ~352 fixed-width
#     table pages fit a phone WITHOUT editing any template.
#     Two guards keep it surgical:
#       * @media (hover:none) and (pointer:coarse) -> TOUCH devices only (phones/
#         tablets). This is true on a phone and FALSE on any laptop/desktop -- even
#         when the desktop window is narrowed -- so the computer UI is NEVER altered
#         (a width media query would wrongly fire on a narrow desktop window).
#       * body:not(.as-menu) -> EXCLUDES the sidebar menu, which is its own iframe
#         that also links archspace.css; menu.html's <body class="as-menu"> makes
#         the rules deterministically skip it (no fragile width-guessing).
#     Marker-guarded so re-runs don't append twice; step 1 re-unpacks the pristine
#     css each boot, so this re-adds the block on top of a clean file every time.
CSS_MAIN="$WWW/archspace.css"
if [ -f "$CSS_MAIN" ] && ! grep -q 'as-mobile-reflow' "$CSS_MAIN" 2>/dev/null; then
    echo "[web] appending mobile reflow rules to archspace.css"
    cat >> "$CSS_MAIN" <<'CSS'

/* === as-mobile-reflow (appended by setup-web.sh) ===========================
   TOUCH DEVICES ONLY -- (hover:none) and (pointer:coarse) is true on phones/
   tablets and FALSE on any laptop/desktop, even with the window narrowed, so the
   computer UI is never changed. The sidebar menu is its own iframe that also
   links this css; body:not(.as-menu) keeps every rule OUT of it (menu.html's
   <body class="as-menu">). For the content pages -- 2004-era fixed-width nested
   tables (610/590/550px) plus hard-coded width= elements like <hr width="550"> --
   cap everything to the viewport (no sideways scroll), scale images, wrap text.
   max-width only ever CAPS (never widens), so capping every width= element is safe. */
@media (hover: none) and (pointer: coarse) {
  body:not(.as-menu) table   { max-width: 100% !important; }  /* auto + fixed tables */
  body:not(.as-menu) [width] { max-width: 100% !important; }  /* <hr width=550>, cells */
  body:not(.as-menu) img     { max-width: 100%; height: auto; }/* scale images */
  body:not(.as-menu) td,
  body:not(.as-menu) th      { overflow-wrap: break-word; }   /* wrap long text */
}
CSS
fi

# 1f) Live top-bar stat flash. When notifications.js updates a PP/Planet/Power
#     value in place (on a tick/war push or after a spend), it adds .as-stat-flash
#     to that <span class="as-stat" data-as-stat="..."> (emitted by head_title.cc)
#     to briefly pulse it so the change is noticeable without a page reload. The
#     pulse fades back to transparent, so the steady-state look is unchanged.
#     Marker-guarded (idempotent); re-added each boot on the pristine css.
if [ -f "$CSS_MAIN" ] && ! grep -q 'as-stat-flash' "$CSS_MAIN" 2>/dev/null; then
    echo "[web] appending live-stat flash rules to archspace.css"
    cat >> "$CSS_MAIN" <<'CSS'

/* === as-stat-flash (appended by setup-web.sh) =============================
   Brief highlight when a live top-bar stat (PP/Planet/Power) updates in place
   via notifications.js. Inline span; the highlight fades to transparent so the
   normal appearance is untouched between updates. */
.as-stat-flash { animation: as-stat-flash 1s ease-out; border-radius: 2px; }
@keyframes as-stat-flash {
  0%   { background-color: rgba(255, 214, 0, .85); color: #000; }
  100% { background-color: transparent; }
}

/* Race emblem in the top status bar: center it on the stat line. The legacy
   <IMG ... ALIGN=absmiddle> aligns the ~17px emblem to the line-box middle,
   which sits a hair above the 13px Times New Roman text's optical center;
   vertical-align:middle (baseline + x-height/2) centers it on the text. The
   emblem is the only image whose src carries "small_symbol", so this is exact. */
img[src*="small_symbol"] { vertical-align: middle; }
CSS
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
