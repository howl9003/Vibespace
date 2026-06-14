# Archspace — Dockerized build & run

This packages the original early-2000s **Archspace** C++ game engine (the
2004–05 source under `archspace_source/archspace/`) so it builds and runs on a
modern host, plus a modern web/auth tier.

## What works today

- The **C++ game server builds** on Ubuntu 24.04 / g++ 13 and **runs**: it loads
  the game scripts, generates the encyclopedia, creates the universe + NPC
  admirals, runs the turn cycle, and listens on TCP **12350**.
- MariaDB schema loads; non-strict `sql_mode` matches the original MySQL.
- Single-image deployment via Docker Compose.

## Build & run

```sh
# from the repo root
docker compose -f docker/docker-compose.yml up --build
```

Then open <http://localhost:8080>. The game server listens internally on
`12350` (not exposed); the web tier is reached via port 8080.

First boot initializes the MariaDB data dir, loads `all.sql` + the modern
`web/auth/schema.sql`, installs the game content layout, and starts the server.
State persists in named volumes (`db_data`, `game_state`, `logs`).

The nginx front end adds conservative browser security headers:
`X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`,
`X-Frame-Options: SAMEORIGIN`, and a restrictive `Permissions-Policy`. A CSP is
intentionally deferred because the preserved 2004 templates still use inline
scripts and styles throughout the UI.

## How the build works (validated step-by-step)

The image (`docker/Dockerfile`) reproduces the exact, tested recipe:

1. Install `g++ make cmake libpth-dev libmariadb-dev mariadb-server/-client
   gettext flex bison nginx php-fpm`.
   - **GNU Pth is packaged** (`libpth-dev`) — no source build needed.
   - Symlink `/usr/include/mysql -> /usr/include/mariadb` (engine includes
     `<mysql.h>`).
2. `cd src && sh set_platform linux` (symlinks the Linux Makefiles).
3. `cd libs && make` → `libarchspace.a`.
4. `cd apps/archspace && make archspace` → the game server binary.

Engine port fixes are committed in the source tree (see git history); the
Makefiles were updated to add `-fpermissive -Wno-write-strings`, the MariaDB
include path, and `-lmariadb`.

## Runtime layout

`docker/setup-runtime.sh` installs the filesystem layout the engine's
`etc/archspace.config` expects:

| Path | Contents |
|---|---|
| `/etc/archspace/` | `archspace.config`, `script/*` (game data), `banner`, `ip_ban`, `admin_list` |
| `/usr/src/archspace/{form,web}` | encyclopedia forms + page templates |
| `/var/archspace/data/` | game state: news, battles, **message bodies**, crontab, audit (persisted) |
| `/var/www/localhost/htdocs/encyclopedia/` | encyclopedia, regenerated at boot |
| `/var/log/archspace/` | `systemlog`, `dblog` |

## Configuration

| Env | Default | Purpose |
|---|---|---|
| `DB_PASS` | `comconq1` | internal MariaDB root password |
| `TZ` | `UTC` | turn-tick / cron timing |
| `SMTP_HOST` … | unset | password-reset email; **unset → reset links are logged** |

## Deploying to a VPS for real hosting

This container can't be publicly hosted from the build environment. To host:
push the image to a registry (or copy the repo), run `docker compose up -d` on
a VPS, and put a TLS-terminating reverse proxy (Caddy/nginx) in front of port
8080. Keep `12350`/MariaDB internal. Back up **both** the `db_data` and
`game_state` volumes.

## Notes / split-DB option

The single-image design matches the engine's pervasive `localhost` assumption.
To split MariaDB into its own container, point the engine's
`[Database] Host` (in `archspace.config`) and the auth service's `DB_HOST` at
the DB service name, and run MariaDB with `sql_mode=NO_ENGINE_SUBSTITUTION`.
