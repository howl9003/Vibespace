# Hosting & deploying on the self-hosted NUC

Since 2026-09 both editions run on **one self-hosted box** (an Intel NUC on
howl's home network) instead of AWS. Nothing on it is reachable directly from
the internet: all traffic, including SSH, comes in through a **Cloudflare
Tunnel**, and SSH is additionally behind a **Cloudflare Access** login.

| Edition | Branch | Checkout on the NUC | Local port | Public URL |
|---|---|---|---|---|
| Faithful original | `production` | `~/archspace` | `127.0.0.1:8080` | https://archspace.cc (+ `www`) |
| cvs-merge restoration | `claude/peng-cvs-merge` | `~/archspace-new` | `127.0.0.1:8081` | https://new.archspace.cc |
| Admin SSH | — | — | `localhost:22` | `ssh.archspace.cc` (Access-gated) |

Each checkout has its own `docker/deploy/.deploy.env` (git-ignored) setting
`COMPOSE_PROJECT_NAME` (`archspace` / `archspace-new`), `WEB_BIND=127.0.0.1`,
`WEB_PORT` and `DEPLOY_BRANCH`, so the two stacks never share containers,
volumes or images. Two more keys go easy on the disk (see *Box notes*):

| Key | `~/archspace` | `~/archspace-new` | Why |
|---|---|---|---|
| `SECOND_PER_TURN` | `120` | `120` | Turn length. Each turn every player's news files (~600 files / ~13 MB per edition) are rewritten, so 2-minute turns halve that write load (the repo default is 60). |
| `STARTUP_DELAY` | — | `60` | Starts the restoration edition half a turn later, so the two editions' startup and their per-turn write bursts don't coincide. Turn timers are per player and start when the engine loads them, so the offset holds. |

Both are applied at container start, so a restart applies a change. No rebuild
is needed.

**There is no auto-deploy right now.** `.github/workflows/deploy.yml` needs a
self-hosted runner, and the old one died with the AWS account. Pushing to
`production` only queues a job that never runs. Deploys are manual over SSH
(below) until a runner is registered on the NUC (see *Admin*).

---

## 1. Get access (contributors)

You need two things added by howl, the NUC admin: your **SSH public key** and
the **email address** you will sign in to Cloudflare Access with.

1. Create a key pair for this box (once):
   ```sh
   ssh-keygen -t ed25519 -f ~/.ssh/archspace-nuc -C "<your name> archspace-nuc"
   ```
2. Send howl the **public** half: the single line in `~/.ssh/archspace-nuc.pub`,
   starting `ssh-ed25519 AAAA...`. **Never send the private key**
   (`~/.ssh/archspace-nuc` without `.pub`).
3. Send the email address you want to use for the Access login.

Everyone logs in as the shared user **`howl`**, which has passwordless `sudo`.
Treat access as root on the box.

## 2. Connect

1. Install `cloudflared`:
   - Windows: `winget install --id Cloudflare.cloudflared` (then open a **new**
     terminal so `cloudflared` is on your `PATH`)
   - macOS: `brew install cloudflared`
   - Debian/Ubuntu: see https://pkg.cloudflare.com (the `cloudflared` repo)
2. Add to `~/.ssh/config`:
   ```
   Host archspace-nuc
     HostName ssh.archspace.cc
     User howl
     IdentityFile ~/.ssh/archspace-nuc
     IdentitiesOnly yes
     ProxyCommand cloudflared access ssh --hostname %h
   ```
3. `ssh archspace-nuc`. The first time (and when the 30-day session expires),
   a browser opens the Cloudflare Access login at
   `archspace.cloudflareaccess.com`: enter your email and the one-time PIN it
   emails you.
4. On the very first connection SSH asks you to trust the NUC's host key. Only
   accept if the fingerprint matches:
   ```
   ED25519  SHA256:eQhhwQjoiKtGhk0D3a7KkQAiGG0ovpEzoryL4A9Pm/Y
   ```

## 3. Deploy

Push your change as usual (see `CLAUDE.md`: feature branch, then fast-forward
`main` + `production` for the faithful edition, or push `claude/peng-cvs-merge`
for the restoration edition). Then run the edition's deploy script **on the
NUC**:

```sh
# faithful edition (archspace.cc) - syncs ~/archspace to origin/production
ssh archspace-nuc 'cd ~/archspace && bash docker/deploy/deploy.sh'

# restoration edition (new.archspace.cc) - syncs ~/archspace-new to origin/claude/peng-cvs-merge
ssh archspace-nuc 'cd ~/archspace-new && bash docker/deploy/deploy.sh'
```

`deploy.sh` fetches the branch, resets the checkout to it, and then either
**restarts** (bind-mounted web/template/config changes, seconds) or
**rebuilds** the image (engine, as-cgi, `src/script` tables, www, Dockerfile;
about 3–5 minutes on the NUC). It diffs against the host-local
`docker/deploy/.last_deployed` marker. `FORCE_REBUILD=1` forces a rebuild.
Game data lives in named volumes and survives both.

Then confirm it is live:
```sh
ssh archspace-nuc 'curl -s localhost:8080/healthz; echo; curl -s localhost:8081/healthz'
```
and load https://archspace.cc / https://new.archspace.cc in a browser.

**Rules on the box**
- Only deploy the edition you changed, from its own checkout. Never run one
  edition's commands in the other's directory.
- Never `docker compose down -v` or remove the `archspace_*` /
  `archspace-new_*` volumes: that deletes the game.
- Keep `WEB_BIND=127.0.0.1`. Docker-published ports bypass `ufw`, so
  `0.0.0.0` would expose the game on the LAN outside the tunnel.
- Don't edit files in the checkouts by hand: `deploy.sh` resets them to the
  branch. Change the repo and deploy instead.

---

## Admin (howl)

**Add a contributor**
1. Append their public-key line to `/home/howl/.ssh/authorized_keys` on the NUC.
2. Cloudflare dashboard: **Zero Trust → Access → Applications → "NUC SSH"**
   (`ssh.archspace.cc`) → **Policies → "Allow NUC admins"**, and add their
   email to the Include rule. Login is by emailed one-time PIN; sessions last
   30 days.

**Remove a contributor**: delete their line from `authorized_keys` and their
email from the Access policy.

**Re-enable auto-deploy (optional)**: in GitHub, **Settings → Actions → Runners
→ New self-hosted runner** (Linux x64), then run the shown `config.sh` and
`sudo ./svc.sh install howl && sudo ./svc.sh start` on the NUC as `howl`. The
workflow runs `deploy.sh` in `$HOME/archspace`, which matches the NUC layout.
Give it a distinct label (e.g. `nuc`).

**Box notes**
- **The M.2 drive (a second-hand Intel 660p 1 TB) is unreliable. Replace it.**
  Under sustained heavy disk I/O it drops off the PCIe bus without logging any
  error. It did this twice on the NUC (mid-build, and when both game engines
  started together) and, per howl, in its previous Windows desktop, where
  Windows recovered. SMART shows healthy flash (7% used, 0 media errors), so
  the controller is suspected, not wear. Replace it with a mainstream TLC SSD,
  ideally with DRAM, or an M.2 SATA drive (256–512 GB).
- **Mitigations in place until then (don't remove them):**
  - `/etc/default/grub.d/99-nuc-nvme-power.cfg`:
    `nvme_core.default_ps_max_latency_us=0 pcie_aspm=off` (no NVMe/PCIe power
    saving) and `nvme_core.io_timeout=120` (wait out stalls instead of
    removing the drive after the default 30 s). Run `sudo update-grub` after
    editing.
  - `/etc/fstab`: root is mounted `errors=panic` (was `errors=remount-ro`),
    and `/etc/sysctl.d/90-nuc-autoreboot.conf` sets `kernel.panic = 10`. If the
    drive vanishes, the kernel panics and **reboots itself** instead of sitting
    half-dead until someone power-cycles it.
  - So **a 2–3 minute outage of both sites plus a fresh uptime means the drive
    dropped and the box recovered itself.** The kernel's last messages are
    saved via EFI pstore and appear after the reboot in
    `/var/lib/systemd/pstore/`. Check there, then `sudo smartctl -a
    /dev/nvme0`, and note it.
- **Backups are not set up yet.** Game data lives only in the `archspace_*` /
  `archspace-new_*` Docker volumes on this one drive.
- BIOS is `SYSKLi35.86A.0054` (2016). The final version, 0073 (2020), exists,
  but Intel has pulled the download and ASUS doesn't support 6th-gen NUCs. Use
  the F7 update with the `.BIO` file from an archived Intel copy, and load
  defaults afterwards. Also set **After Power Failure = Power On**.
- The CMOS battery is unreliable, so after a power cut the clock is wrong until
  NTP syncs (about a minute). apt can fail with "Release file not valid yet" in
  that window.
- `docker/deploy-ec2.md` documents the retired AWS setup and is kept for
  history only.
