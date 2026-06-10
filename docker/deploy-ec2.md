# Deploying Archspace to AWS EC2

A single Docker image runs the whole stack (game engine + MariaDB + web). You
build it on the instance. Budget ~2–5 min for the first build (it compiles the
C++ engine).

## 1. Launch an EC2 instance

| Setting | Recommendation |
|---|---|
| **AMI** | Ubuntu Server 24.04 LTS (x86_64) |
| **Type** | `t3.medium` (4 GB) for a smooth build. `t3.small` (2 GB) works — the bootstrap adds 2 GB swap so the compile won't OOM. |
| **Storage** | 20 GB gp3 |
| **Security group (inbound)** | SSH `22` from *your IP*. Plain HTTP: TCP `8080` (or `80`) from `0.0.0.0/0`. HTTPS: `80` **and** `443` from `0.0.0.0/0` (Caddy needs `80` for the ACME challenge + redirect). |

## 2. Get the code onto the instance

SSH in (`ssh -i your-key.pem ubuntu@<public-ip>`), then either:

**a) git clone** (repo is private — use a GitHub Personal Access Token or deploy key):
```sh
git clone https://<TOKEN>@github.com/howl9003/Vibespace.git archspace
cd archspace && git checkout production   # the instance tracks the deploy branch
```

**b) or copy from your machine:**
```sh
# from your laptop, in the repo dir:
rsync -az --exclude .git ./ ubuntu@<public-ip>:~/archspace/
```

## 3. Build + run (one command)

**Plain HTTP** (quickest, good for a first smoke test):
```sh
cd ~/archspace
sudo WEB_PORT=8080 bash docker/deploy/ec2-bootstrap.sh
```
Use `WEB_PORT=80` to serve on the bare URL (`http://<public-ip>/`). When it
finishes it prints the URL — open **`http://<public-ip>:8080/`**.

**HTTPS with a domain** (recommended for real testing): point a DNS A record at
the instance's public IP first, then:
```sh
cd ~/archspace
sudo DOMAIN=play.example.com TLS_EMAIL=you@example.com \
     bash docker/deploy/ec2-bootstrap.sh
```
This brings up Caddy on `80`/`443` in front of the app (which stays internal on
`127.0.0.1:8080`), provisions a Let's Encrypt certificate automatically, and
redirects HTTP→HTTPS. Open **`https://play.example.com/`** (the first request
may take a few seconds while the cert is issued). Make sure inbound **80 and
443** are open in the Security Group.

Either way you should get the login page → register → create a character → play,
with real-time notifications pushing into the dashboard news feed.

## 4. Operate

```sh
cd ~/archspace
docker compose -f docker/docker-compose.yml logs -f      # follow logs
docker compose -f docker/docker-compose.yml ps           # status/health
docker compose -f docker/docker-compose.yml restart      # restart
docker compose -f docker/docker-compose.yml down         # stop (keeps volumes)
```
State persists in named volumes (`db_data`, `game_state`, `logs`). Back up
**both** `db_data` and `game_state` (the latter holds news/battles/messages).

## 4b. Automatic updates (GitHub Actions → SSH)

After the first manual bootstrap, you don't need to SSH in for updates. A
workflow (`.github/workflows/deploy.yml`) runs on every push to the dedicated
**`production`** branch (and on demand), SSHes into the instance, and runs
`docker/deploy/deploy.sh` — which pulls the new code and rebuilds/restarts the
stack (reusing the HTTP/HTTPS config the bootstrap saved in
`docker/deploy/.deploy.env`). The image is built **on the instance**, same as
the manual flow; the rebuild only blips the container at the swap, and the DB +
game state in the named volumes survive.

**Develop freely, ship deliberately.** Day-to-day commits land on a feature
branch and do *not* deploy. You ship by advancing `production`:
```sh
# from your feature branch, when you're ready to release:
git checkout production
git merge --ff-only claude/festive-bohr-xk9fwc   # or your feature branch
git push origin production                        # -> triggers the deploy
```
The instance tracks `production`, so the workflow deploys exactly what you
pushed there. (Until the EC2 secrets below are set, the workflow runs but
cleanly skips the deploy step.)

**One-time setup** — add these repository secrets
(*Settings → Secrets and variables → Actions*):

| Secret | Value |
|---|---|
| `EC2_HOST` | instance public IP or DNS |
| `EC2_USER` | SSH user (e.g. `ubuntu`) |
| `EC2_SSH_KEY` | a **private** key whose public half is in the instance's `~/.ssh/authorized_keys` |
| `EC2_PORT` *(optional)* | SSH port if not `22` |
| `EC2_REPO_DIR` *(optional)* | repo path on the instance if not `~/archspace` |

Tips:
- Use a **dedicated deploy key pair** for Actions (don't reuse your personal
  key): `ssh-keygen -t ed25519 -f deploy_key`, append `deploy_key.pub` to the
  instance's `authorized_keys`, and paste `deploy_key` (the private file) into
  `EC2_SSH_KEY`.
- The instance also needs to authenticate its own `git fetch` of this **private
  repo**. The simplest durable option is a read-only **GitHub deploy key** on
  the instance (`ssh-keygen` there, add the public key under the repo's
  *Settings → Deploy keys*, and set the remote to the SSH URL:
  `git remote set-url origin git@github.com:howl9003/Vibespace.git`). A
  fine-grained PAT in a git credential helper works too.
- `ec2-bootstrap.sh` adds the login user to the `docker` group, so the deploy
  runs Docker without sudo.
- To change which branch auto-deploys, edit the `branches:` list in the
  workflow.

You can also trigger a deploy by hand from the **Actions** tab
(*Run workflow*), or run `bash docker/deploy/deploy.sh` directly on the box.

## 5. HTTPS + a domain (integrated Caddy)

HTTPS is built into the compose stack via the `https` profile (see step 3) — no
separate `docker run` needed. The `caddy` service terminates TLS on `80`/`443`,
auto-renews the certificate, and reverse-proxies to the app over the internal
network (the SSE push endpoint is proxied unbuffered so notifications still
arrive in real time).

To start/stop it manually (outside the bootstrap):
```sh
DOMAIN=play.example.com TLS_EMAIL=you@example.com \
  docker compose -f docker/docker-compose.yml --profile https up --build -d
```
Certs persist in the `caddy_data` volume. If issuance fails, check
`docker compose logs caddy` — the usual causes are DNS not yet pointing at the
instance or port `80` blocked in the Security Group.

## Email (password reset)

Reset links are **logged** (in `/var/log/archspace/mail.log` inside the
container) unless you set SMTP. To send real email, add to the `environment:`
block in `docker/docker-compose.yml`:
```yaml
      SMTP_HOST: email-smtp.us-east-1.amazonaws.com   # e.g. AWS SES
      SMTP_PORT: 587
      SMTP_USER: <ses-smtp-user>
      SMTP_PASS: <ses-smtp-pass>
      SMTP_FROM: noreply@yourdomain.com
```

## Real-time notifications (push)

Incoming diplomatic/council messages and hostile actions (siege, blockade,
raid, privateer, spy) are pushed to the browser in real time over
Server-Sent Events (`/auth/events.php`) — no extra port, it rides the same
HTTP port as the rest of the site. The dashboard's news feed refreshes the
instant an event lands. Each open stream holds one php-fpm worker for ~55s
before the browser reconnects; the pool is sized for this
(`PHP_FPM_MAX_CHILDREN`, default 50 — raise it via the `environment:` block
for very large player counts). If you put a reverse proxy in front, make sure
it doesn't buffer `text/event-stream` (nginx and Caddy both pass SSE through
fine by default).

## Troubleshooting

- **Build OOM / killed** on a tiny instance → use `t3.medium`, or confirm the
  2 GB swap was added (`swapon --show`).
- **Can't reach the site** → check the Security Group inbound rule for the port,
  and `docker compose ps` shows the container healthy.
- **502 on a page** → `docker compose logs` to see if php-fpm / the game came up;
  the game listens internally on 12350 (not exposed).
- **Notifications don't pop in real time** → confirm `/auth/events.php` returns
  an event stream (`curl -N http://<ip>:<port>/auth/events.php` with a logged-in
  `as_session` cookie should hold open and emit `event: ready`); make sure any
  front proxy isn't buffering SSE.
