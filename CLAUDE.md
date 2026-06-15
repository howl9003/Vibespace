# CLAUDE.md — agent guide for this repo

Shared operating rules for AI coding agents (Claude Code) working on Archspace.
**Both collaborators' agents auto-load this file**, so put durable conventions
here instead of repeating them in every prompt. For architecture/build/deploy
depth see **README.md**; for deferred ideas see **WISHLIST.md**.

## Orientation
Archspace is a ~20-year-old C++ space-strategy game server, modernized to run as
a single Docker image (MariaDB + game server + nginx web tier). It has three
tiers in increasing cost/risk — **always prefer the lowest tier that solves the
task:**

1. **UX tier** — nginx, page templates (`src/web`), `docker/web-overrides`, setup
   scripts. Bind-mounted, so changes ship as a **restart** (seconds). No recompile.
2. **Adapter** — `docker/as-cgi` (the CGI bridge). Recompiled → **rebuild**.
3. **Engine** — C++ under `archspace_source/archspace/src/{libs,apps}` →
   **rebuild** (minutes). Touch only when the behavior genuinely lives here.

Replacing a dead applet or a cosmetic fix is almost never an engine change.

## Branches & deploying
- Develop on your **own** feature branch, namespaced per collaborator:
  `claude/<handle>-<topic>` (e.g. `claude/howe-expeditions`). **Do not share a
  feature branch** between collaborators.
- `main` = mainline; `production` = **deploy branch** (pushing there deploys via a
  self-hosted runner). Engine/as-cgi/Dockerfile change → rebuild; everything else
  → restart — `docker/deploy/deploy.sh` decides via a host-local marker.
- Ship by fast-forwarding `main` **and** `production` to your reviewed feature tip.
- **Always watch the deploy to green** (GitHub Actions `deploy.yml`, branch
  `production`) and confirm the change live before calling it done.

## Working alongside the other collaborator (important)
`main`/`production` **move without warning** because the other collaborator's
agent deploys too. So:

- **`git fetch` before every push.** Never assume your local view of
  `main`/`production` is current.
- If the remote moved, **rebase your feature branch onto `origin/main`** and
  re-verify. Do not merge-commit, and **never discard or force past their
  commits** — their work must survive.
- **Never force-push `main` or `production`.** Use `git push --force-with-lease`
  only on your *own* `claude/*` branch, and only right after a rebase.
- When you build on top of something the other collaborator pushed, **say so**
  and keep their commit intact.
- If their change conflicts with your task, **surface it to your human** instead
  of silently resolving it.

## Verify before you push (a failed build = a failed, slow production deploy)
- **Engine / as-cgi (C++): compile to object files, not just `-fsyntax-only`.**
  Macros like `ITEM(...)` expand to brace blocks, so `-fsyntax-only` can miss real
  errors (e.g. `if/else` around `ITEM(...)` → *"else without a previous if"*).
  Build the changed `.o` with the project include flags
  (`-I../../libs/include -I/usr/include/pth -I/usr/include/mariadb ...`) or run a
  local `docker compose -f docker/docker-compose.yml up --build`.
- **Web/JS:** `node --check` any changed JS override; eyeball the template.

## Web assets & image routing (read before touching `/image/...`)
The web root is assembled at boot by `docker/setup-web.sh`, in this order:
1. **The original `www` tarball** (`archspace.tar.gz`) is the **canonical, complete,
   known-good** asset tier — unpacked first. It already contains almost every
   `/image/...` the game and encyclopedia reference.
2. **`www-new/`** is a **supplemental** tree, overlaid *only to fill genuine gaps*
   the tarball lacks (e.g. planet thumbnails). It is overlaid with **`cp -n`
   (no-clobber)** so it can **never overwrite a good tarball image**.

**Why no-clobber matters (a real incident):** `www-new/image/**` had 900+
byte-corrupted GIFs. Root cause: a Windows/CVS **text-mode import rewrote every
`0x0A` byte to `0x0D0A`** inside the binaries (an LF→CRLF mangle). The overlay
used to `cp -f` them *over* the good tarball images, so the encyclopedia,
deploy-board ship icons, etc. rendered broken. Dual-present images were first
masked by switching the overlay to `cp -n` (tarball wins); the corrupt binaries
were then **repaired in place by reversing the mangle** (`0x0D0A`→`0x0A`) across
the served tree and the archival `CVSRoot/` snapshot, proven byte-exact (every
repaired file that also exists in the tarball matches it). **Rules:**
- The **tarball wins**; `www-new` only fills gaps. Don't change the overlays back
  to `cp -f`.
- A broken in-game image is almost always a *missing/corrupt asset in the web
  root*, **not** an engine bug. The image-server prefix is blanked at boot, so
  paths are same-origin `/image/...`; a broken image is a 404 or a corrupt file,
  not a bad URL.
- `.gitattributes` marks `*.gif/*.png/...` as `binary` so checkouts can't
  re-corrupt them. **Keep it.** When adding image assets, verify they decode
  (`file x.gif` / open them) before committing.
- The `www-new`-exclusive assets with **no tarball fallback** (the
  `image/as_game/planets/` thumbnails and the `encyclopedia/special_ops/*` icons)
  were CRLF-corrupted and are **now repaired** in the served tree.
- A legacy Korean "under construction" placeholder was served for 35 unfinished
  art slots (the black market, 17 diplomacy spy-op icons, 13 fleet-action
  buttons, `ending_score` / `retire` / `event/meeting` / `result/new_player_assign`).
  `setup-web.sh` now swaps every served copy (matched by content hash) for a clean
  English `placeholder_en.gif`; the black-market no-item/error/bid-result slots
  instead reuse the existing `title_black_market.gif` banner.

## Gotchas
- `CString → char*` now returns `""` (not `NULL`) for empty strings. Old
  `if (x != NULL)` checks can emit empty UI elements; guard at the source with
  `if (!x || !*x)`.
- The UI font is normalized to **Times New Roman** at web-root assembly
  (`docker/setup-web.sh`) — keep it; that sed is idempotent and bounded.
- Race ids `1..10` map to image folders under `/image/as_game/race/<name>/` in
  `src/script/race.en` order (Human … Xesperados).
