# Archspace — Build, Run & Deploy

A working, self-hosted revival of **Archspace** — an early-2000s persistent,
browser-based 4X space-strategy game (a C++ engine, ~449 source files). This
repo takes the original 2004–05 source and turns it into a reproducible,
Dockerized, HTTPS-served deployment with push-to-deploy CI/CD, while keeping the
game itself faithful to the original.

Live deployments — **two editions** (see [Two editions](#two-editions)):
- **https://archspace.cc** — the **faithful original** edition (`main` → `production`).
- **https://new.archspace.cc** — the **cvs-merge restoration** edition (branch
  `claude/peng-cvs-merge` only): the same engine with a large body of original
  content restored from the game's CVS history.

---

## Guiding principle (three tiers)

Every change falls into one of three tiers, with different rules:

| Tier | Rule |
|---|---|
| **Game logic & mechanics** | **Strictly faithful** — no rule, balance, or formula changes. The C++ engine computes the game exactly as the original did. |
| **UX / UI** | **Original look + modern QoL.** Keep the 2004 visual identity and art, but de-frame the shell, make it mobile-friendly, add AJAX/real-time refresh, and replace dead Java applets with HTML5. No "modern reskin." |
| **Backend / plumbing** | **Modernize freely.** Fidelity is *not* a reason to keep old plumbing — hence modern PHP auth, nginx, a CGI adapter, Docker, and CI/CD replace the legacy portal/relay/`mod_as`/multi-daemon stack. |

When in doubt: the engine binary is sacred; everything around it is fair game.

---

## Repository layout

```
.
├── archspace_source/archspace/     # The original game (engine + content)
│   ├── src/                        # Engine source
│   │   ├── libs/                   # Custom libraries -> libarchspace.a
│   │   │   ├── cgi/                #   page/HTTP-ish request + template engine
│   │   │   ├── database/           #   MySQL/MariaDB client wrapper (CMySQL)
│   │   │   ├── net/ frame/ key/    #   sockets, framing, crypto/keys
│   │   │   ├── runtime/ util/      #   zones, lists, CString, scheduler
│   │   │   └── include/            #   shared headers
│   │   ├── apps/archspace/         # The GAME SERVER (~449 files)
│   │   │   ├── *.cc *.h            #   model: player, planet, fleet, council, tech…
│   │   │   ├── page/               #   page handlers (one per *.as URL), grouped:
│   │   │   │   ├── domestic/ diplomacy/ council/ fleet/ war/
│   │   │   │   ├── empire/ blackmarket/ info/ admin/ …
│   │   │   │   └── main.cc menu.cc create2.cc events.cc …
│   │   │   ├── trigger/            #   per-turn crontab processing
│   │   │   ├── Makefile.Linux      #   build (selected by ../set_platform linux)
│   │   │   └── DB/all.sql          #   game database schema
│   │   ├── web/                    # Engine HTML templates ($TOKEN-substituted at runtime)
│   │   ├── form/                   # Encyclopedia + form templates
│   │   ├── script/                 # Game DATA tables (.en): races, tech, ships, projects…
│   │   └── set_platform            # Symlinks Makefile.<Platform> into place
│   ├── etc/                        # archspace.config, banner, ip_ban
│   ├── www-new/                    # Newer web tier (forum/phpMyAdmin) — mostly unused,
│   │                               #   but the only source of planet thumbnails
│   ├── portal/  mod_as-*/          # Legacy auth portal + Apache module — DROPPED
│   └── archspace.tar.gz            # Packaged original `www` tier + content (art,
│                                   #   encyclopedia, static pages) — content source of truth
│
├── docker/                         # The modern deployment
│   ├── Dockerfile                  # Compiles the engine on ubuntu:24.04 / g++ 13
│   ├── docker-compose.yml          # Single-image stack (+ optional `https` profile = Caddy)
│   ├── entrypoint.sh               # Boot orchestration (DB, runtime, web, game server)
│   ├── setup-runtime.sh            # Installs config + game content + templates
│   ├── setup-web.sh                # Assembles the web root (www tier + auth + overrides)
│   ├── nginx.conf                  # Front-end routing (static / PHP / *.as / SSE / healthz)
│   ├── as-cgi/                     # CGI adapter: HTTP *.as  <->  game binary protocol
│   ├── web-overrides/              # De-framed shell, notifications.js, refresh.js, applet replacements
│   ├── caddy/Caddyfile             # HTTPS reverse proxy (apex + www redirect)
│   ├── deploy/
│   │   ├── ec2-bootstrap.sh        #   one-command EC2 setup (Docker, swap, build, run)
│   │   └── deploy.sh               #   pull + rebuild-or-restart (marker-based detection)
│   ├── deploy-ec2.md               # Full EC2 deployment guide
│   └── README.md                   # Docker build/run notes
│
├── web/auth/                       # Modern PHP auth service (served at /auth/)
│   ├── login.php register.php logout.php forgot.php reset.php
│   ├── lib.php                     #   sessions, routing helpers, mail
│   ├── db.php                      #   shared mysqli connection (env-configured)
│   ├── events.php                  #   real-time push bridge (Server-Sent Events)
│   ├── theme.php                   #   original-look styling for the auth pages
│   └── schema.sql                  #   accounts + sessions tables
│
└── .github/workflows/deploy.yml    # Self-hosted-runner deploy on push to `production`
```

---

## Runtime architecture

Everything runs in **one container** (the engine assumes localhost throughout).
The entrypoint brings the pieces up in order:

```
                         ┌─────────────────────────── container ───────────────────────────┐
  browser ── HTTPS ──►  Caddy (:80/:443, optional)  ──►  nginx (:80)
                                                          │
                          ┌───────────────────────────────┼────────────────────────────────┐
                          ▼                                ▼                                 ▼
                   /auth/*.php                          *.as                        /image, /encyclopedia,
                   /*.php (www tier)              fcgiwrap → as-cgi                  static html, /healthz
                          │                              │  (binary protocol)              │
                          ▼                              ▼                                 ▼
                     php-fpm  ◄── DB_*            game server  (TCP 12350, localhost)   filesystem
                          │                              │
                          └──────────────┬───────────────┘
                                         ▼
                                  MariaDB (localhost)
                                  DB `Archspace`: game tables + accounts/sessions
```

**Processes (started by `docker/entrypoint.sh`):**
1. **MariaDB** — initializes on first boot, loads `all.sql` + `web/auth/schema.sql`.
2. **setup-runtime.sh** — installs `/etc/archspace/*`, game scripts, and HTML
   templates into the paths the engine reads; rewrites the `$IMAGE_SERVER_URL`
   token to same-origin `/image`.
3. **setup-web.sh** — assembles the web root from the `www` tarball + the modern
   auth service + `web-overrides`.
4. **php-fpm** — runs the auth service and the SSE bridge.
5. **fcgiwrap + as-cgi** — bridges `*.as` requests to the game server.
6. **nginx** — the public web front.
7. **game server** (`/usr/sbin/archspace`) — listens on **12350** (localhost),
   speaks a binary message protocol, renders HTML from templates.

### Request flow & the de-framed shell

The game is reached through the de-framed shell at **`/archspace/index.html`**
(`docker/web-overrides/archspace/index.html`): a CSS grid with two named
iframes — `menu.as` (left navigation) and `main.as` (the dashboard/content). The
original obsolete `<frameset>` is gone; the look is unchanged and it works on
mobile.

- A `*.as` request → nginx → fcgiwrap → **as-cgi** → game server (binary
  protocol) → HTML back to the browser. as-cgi forwards the `as_session` cookie
  so the engine resolves the logged-in player.
- Static assets (`/image/...`, `/encyclopedia/...`) are served directly by nginx
  from the unpacked `www` tier.

### Authentication (modern, replaces the legacy portal)

`web/auth/` is a small PHP service: email/password with Argon2id hashing,
DB-backed sessions (`as_session` cookie), and password reset (SMTP if
configured, otherwise reset links are logged). The game keys a player by
`player.portal_id == accounts.id`. Routing: a logged-in account with a character
goes to the shell; without one, to the standalone create-character page.

### Real-time push (Server-Sent Events)

Diplomatic/council messages and hostile actions (siege/blockade/raid/spy)
resolve immediately in the engine. `web/auth/events.php` holds a browser
`EventSource` open and drains a lightweight engine fingerprint
(`/archspace/events.as` — turn + unread counts + pending events). When it
advances, it pushes an `update` and the dashboard refreshes its news feed in
place — so events appear without a manual reload. Content and appearance are the
original feed; only the *timing* changes.

---

## Build

The image compiles the ~20-year-old engine on a modern toolchain
(`ubuntu:24.04`, g++ 13) with a small, tracked set of portability fixes
(`-fpermissive`, GNU Pth via `libpth-dev`, MariaDB client via `libmariadb-dev`
with a `/usr/include/mysql → mariadb` symlink, a handful of `NULL`/return-type
fixes). `set_platform linux` selects the Linux Makefiles; `libs/` builds
`libarchspace.a`, then `apps/archspace/` links the server. The **as-cgi** adapter
(replacing the original Apache `mod_as`) is built in the same image.

```sh
# Local build + run (plain HTTP on :8080)
docker compose -f docker/docker-compose.yml up --build
# → http://localhost:8080/
```

State persists in named volumes: `db_data` (MariaDB), `game_state`
(`/var/archspace/data` — news, battles, messages, audit), `logs`.

### Fast iteration vs. full rebuild

`docker-compose.yml` **bind-mounts** the web tier, page templates, nginx config,
and setup scripts, and the entrypoint re-assembles them on every start. So:

- **Web / UI / template / config change** → `restart` re-applies it in seconds.
- **C++ engine / as-cgi / Dockerfile change** → full image rebuild (minutes).

`docker/deploy/deploy.sh` decides automatically (see below).

---

## Database

Single MariaDB instance, DB `Archspace`, non-strict `sql_mode`
(`NO_ENGINE_SUBSTITUTION`) to match 2004 MySQL behavior. Schemas:
- `archspace_source/archspace/src/apps/archspace/DB/all.sql` — the game.
- `web/auth/schema.sql` — `accounts` + `sessions` (modern auth).

Credentials come from env (`DB_HOST/DB_USER/DB_PASS/DB_NAME`), defaulting to the
original `root` / `comconq1` **inside the container network only** (not exposed).

---

## Two editions

This repo maintains **two editions** of the game, deployed to two separate hosts:

| Edition | Branch(es) | Live site | Deploy |
|---|---|---|---|
| **Faithful original** | `main` → `production` | **https://archspace.cc** | push-to-deploy (self-hosted runner) |
| **cvs-merge restoration** | `claude/peng-cvs-merge` **only** | **https://new.archspace.cc** | manual `deploy.sh` over SSH |

**Faithful original** holds to the three-tier "strictly faithful" rule above:
the original 2004–05 game — no rule, balance, or formula changes. `main` is the
mainline; `production` is its deploy branch.

**cvs-merge restoration** restores a large body of original content recovered
from the game's CVS history that the faithful edition omits, and reworks some
mechanics. Highlights:
- 11th playable race **Trabotulin** (with its own commander racial abilities).
- A **4-skill commander** model (Offense / Defense / Maneuver / Detection) plus
  per-race commander racial abilities.
- Two megaclass hulls — **Astral Carrier** (class 11) and **Suncrusher**
  (class 12) — gated on specific schematics; ship designs widened to 10 weapon slots.
- An extended tech tree (obtainable tech tops out at 190).
- A tiered, self-running NPC **bot** population (Newbie … Supreme Admiral).
- More restored components / projects / events / spy ops.

These are deliberate gameplay divergences, so the "strictly faithful" rule does
**not** apply to the restoration edition. The restoration lives **only** on
`claude/peng-cvs-merge` — **do not merge it into `main` or `production`.**

**Why this matters (an incident worth knowing).** The restoration was once merged
into `main` and then shipped to `production`, which **took prod down** — the
faithful engine can't read the migrated DB (4-skill admiral table, widened ship
classes) and crashes on load. `production` was **reverted to the pre-cvs-merge
snapshot** (forward commit `4446f6b0` — no force-push), and the restoration was
**reverted out of `main`** (`1a1fd5e4`, `dad0189e`), so both are faithful again.
The restoration is a separate edition on its own branch; keep it there. (One
residue: `production` still carries incident-recovery DB-reversal hotfixes in
`entrypoint.sh` that `main` lacks, so the two faithful trees aren't byte-identical.)

---

## Deployment & CI/CD

### Branch model

| Branch | Role |
|---|---|
| `main` | mainline for the **faithful** edition (archspace.cc) |
| `production` | deploy branch for the **faithful** edition — pushing here triggers a deploy |
| `claude/peng-cvs-merge` | the **restoration** edition — deployed **manually** to new.archspace.cc; **not** merged into `main` |
| other feature branches (`claude/*`) | active development; do **not** deploy |

Ship **faithful** fixes through `main` → `production`; ship **restoration** work
to `claude/peng-cvs-merge`. **Never merge the restoration into `main`/`production`**
— that re-triggers the prod incident described in [Two editions](#two-editions).
`production` carries incident-recovery hotfixes `main` lacks, so reconcile the two
faithful trees by cherry-pick rather than a blind fast-forward.

> **Multiple contributors:** `main`/`production` can move because another
> collaborator's agent deploys too. Always `git fetch` before pushing; if the
> remote moved, **rebase your feature branch onto `origin/main`** and re-verify —
> never force-push the shared branches or discard the other's commits. Agent
> conventions live in **[`CLAUDE.md`](CLAUDE.md)** (auto-loaded by Claude Code).

### Push-to-deploy (the faithful edition — archspace.cc)

`.github/workflows/deploy.yml` runs on a **self-hosted GitHub Actions runner
installed on the EC2 instance**. The runner dials *out* to GitHub — so there's
**no inbound SSH, no open port 22, no SSH key/secret, and the public IP can
change freely**. On a push to `production` it runs `docker/deploy/deploy.sh`,
which:

1. Pulls the new commit.
2. Diffs it against the **last-deployed marker** (`docker/deploy/.last_deployed`,
   host-local) — *not* git HEAD, because the workflow already reset HEAD. (This
   marker is the fix for a subtle bug where every deploy silently took the
   no-rebuild path.)
3. **Rebuilds** if the engine / as-cgi / Dockerfile changed; otherwise
   **restarts** (web/template/config). `FORCE_REBUILD=1` overrides.
4. Records the new commit in the marker.

The DB and game-state volumes survive; only a brief blip at the container swap.

### First-time setup on EC2

See **`docker/deploy-ec2.md`** for the full walkthrough. In short:
1. Ubuntu 24.04 instance + **Elastic IP**; security group: SSH `22` (your IP),
   and `8080` (HTTP) or `80`+`443` (HTTPS).
2. Clone via a read-only **GitHub deploy key**; `git checkout production`.
3. `sudo bash docker/deploy/ec2-bootstrap.sh` (installs Docker, swap if needed,
   builds, runs; persists config to `docker/deploy/.deploy.env`).
4. Install the self-hosted runner as a service (`svc.sh install && svc.sh start`).

### HTTPS + domain

Opt-in via the compose **`https` profile** (Caddy):
```sh
sudo DOMAIN=archspace.cc TLS_EMAIL=you@example.com bash docker/deploy/ec2-bootstrap.sh
```
Caddy fronts `:80/:443`, auto-provisions/renews Let's Encrypt certs, redirects
http→https, serves the apex, and 301-redirects `www` → apex. DNS: `A @` and
`A www` → the Elastic IP. Once `DOMAIN` is in `.deploy.env`, every future
auto-deploy stays on HTTPS.

### Deploying the restoration edition (new.archspace.cc)

The cvs-merge restoration runs on a **separate** EC2 box with **no auto-deploy
runner** — updates are manual:

1. Commit and push your change to **`claude/peng-cvs-merge`**.
2. SSH to the staging box (`ssh -i <key>.pem ubuntu@<host>`).
3. `cd ~/archspace && bash docker/deploy/deploy.sh`. The box's
   `docker/deploy/.deploy.env` pins `DEPLOY_BRANCH=claude/peng-cvs-merge`, so
   `deploy.sh` fetches that branch, resets to it, then rebuilds or restarts.
   Use **`FORCE_REBUILD=1 bash docker/deploy/deploy.sh`** for any image-baked
   change (the C++ engine, the `src/script/*.en` data tables, the `www` tarball,
   or the Dockerfile).

The box checks out via a **read-only deploy key** (it can fetch, not push), so
always push from your own machine. As with prod, the DB and game-state named
volumes survive a rebuild, so characters persist.

> The restoration is intentionally kept **off `production`** after it broke prod
> (see [Two editions](#two-editions)). Ship it to this staging box only, until
> prod is ready for the migrated DB schema.

---

## Engine concepts worth knowing

- **`game_id`** — a player's in-game number, assigned at character creation as
  `max(existing)+1` (starts at 1; the Empire is `0`). Pages resolve players via
  `PLAYER_TABLE->get_by_game_id()`.
- **News system** — the main page shows a turn summary (`news_turn → turn`
  range) plus itemized tech/planet/project/admiral news and time-stamped events.
  Rendering is **read-only and accumulates across the per-turn auto-refresh**;
  the feed is *consumed* (baselines advanced, lists cleared) only when the player
  **navigates away** from the dashboard (`CPlayer::acknowledge_news`). This is
  what makes building counts and deltas sum over unseen turns instead of resetting.
- **Encyclopedia** — generated at boot into `/encyclopedia/*` from
  `form/encyclopedia/*` templates + the `script/*` data tables.
- **Game balancing** — two layers: per-entity declarative data in `script/*.en`
  (e.g. `<Tech>{ Number(), Level(), Cost(), <Prerequisite>, <Effect> }`) and
  global knobs in `etc/archspace.config`. Both are data, not code.
- **Turn speed** — `mSecondPerTurn` (shipped 60s), env-configurable.

### A recurring gotcha

The engine's `CString → char*` conversion was changed to return `""` (not
`NULL`) for an empty string, to fix `(null)` appearing in image URLs. Several
old call sites used `if (x != NULL)` to mean "has content," so they can emit
empty UI elements after the change. Two were found and guarded (empty planet
reports; blank per-turn time-news rows). If a stray "empty but present" element
appears, suspect this pattern and add an `if (!x || !*x)` guard at the source.

---

## Local development

Start the stack from the repo root:
```sh
docker compose -f docker/docker-compose.yml up --build
```

For web/UI/template changes, restart the existing container instead of
rebuilding. The auth pages, page templates (`src/web`), and overrides are
bind-mounted, and the entrypoint re-assembles the web root on start:
```sh
docker compose -f docker/docker-compose.yml restart
curl http://localhost:8080/healthz
```
The health check should return `ok`.

For engine C++ or `docker/as-cgi` changes, rebuild the image:
```sh
docker compose -f docker/docker-compose.yml up --build
```

Useful logs while iterating:
```sh
docker compose -f docker/docker-compose.yml logs -f
```
