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
| **Security group (inbound)** | SSH `22` from *your IP*; TCP `8080` (or `80`) from `0.0.0.0/0` for the game. Add `443` if you set up HTTPS. |

## 2. Get the code onto the instance

SSH in (`ssh -i your-key.pem ubuntu@<public-ip>`), then either:

**a) git clone** (repo is private — use a GitHub Personal Access Token or deploy key):
```sh
git clone https://<TOKEN>@github.com/howl9003/Vibespace.git archspace
cd archspace && git checkout claude/festive-bohr-xk9fwc
```

**b) or copy from your machine:**
```sh
# from your laptop, in the repo dir:
rsync -az --exclude .git ./ ubuntu@<public-ip>:~/archspace/
```

## 3. Build + run (one command)

```sh
cd ~/archspace
sudo WEB_PORT=8080 bash docker/deploy/ec2-bootstrap.sh
```
This installs Docker, adds swap if needed, then `docker compose up --build -d`.
Use `WEB_PORT=80` to serve on the bare URL (`http://<public-ip>/`).

When it finishes it prints the URL. Open **`http://<public-ip>:8080/`** and you
should get the login page → register → create a character → play.

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

## 5. Optional: HTTPS + a domain

Point a domain at the instance and run Caddy in front for automatic TLS:
```sh
# example: map 80/443 -> the game on 8080
sudo docker run -d --name caddy --network host \
  -v caddy_data:/data caddy:2 \
  caddy reverse-proxy --from yourdomain.com --to localhost:8080
```
Open `443` (and `80` for the ACME challenge) in the security group. Then the
game is at `https://yourdomain.com/`.

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

## Troubleshooting

- **Build OOM / killed** on a tiny instance → use `t3.medium`, or confirm the
  2 GB swap was added (`swapon --show`).
- **Can't reach the site** → check the Security Group inbound rule for the port,
  and `docker compose ps` shows the container healthy.
- **502 on a page** → `docker compose logs` to see if php-fpm / the game came up;
  the game listens internally on 12350 (not exposed).
