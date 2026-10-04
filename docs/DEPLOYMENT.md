# Deployment — GitHub Actions auto-deploy (CloudPanel)

This project deploys with a **self-hosted GitHub Actions runner** installed on the
server. The server dials *out* to GitHub and pulls the code itself, so **no SSH keys
or passwords are handed to GitHub**.

| Branch       | Environment / runner label | Server pulls      |
|--------------|----------------------------|-------------------|
| `production` | `production`               | `origin/production` |
| `dev`        | `testing`                  | `origin/dev`      |

Workflow: [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml)
Deploy script (runs on the server): [`.github/scripts/deploy.sh`](../.github/scripts/deploy.sh)

---

## Live server (societynew.in)

| | |
|---|---|
| Panel        | CloudPanel (`https://88.222.245.214:8443`) |
| IP           | `88.222.245.214` |
| Site user    | `societynew1` |
| Laravel root | `/home/societynew1/htdocs/societynew.in/easylogics-laravel` |
| Web root     | `.../easylogics-laravel/public` (set in CloudPanel → Site → Settings → Root Directory) |

`DEPLOY_PATH` = the Laravel root above (the folder that contains `.git`, `artisan`,
`composer.json`), **not** the `public/` folder.

---

## One-time setup

### 1. Make the Laravel root a git checkout

SSH in as the **site user** (`societynew1`) and clone the repo into the deploy path:

```bash
cd /home/societynew1/htdocs/societynew.in
# If easylogics-laravel already exists with live data, back it up first:
#   mv easylogics-laravel easylogics-laravel.bak
git clone https://github.com/easylogicstechnology/easylogics-laravel.git easylogics-laravel
cd easylogics-laravel
git checkout production
```

> The deploy script runs `git reset --hard origin/production`, so the working tree is
> always forced to match the branch. Local edits to **tracked** files are snapshotted
> to `../deploy-backups/*.patch` before the reset (nothing is silently lost), but
> untracked live data (`storage/app` uploads, `.env`) is never touched.

### 2. Create `.env` on the server (gitignored — never committed)

```bash
cp .env.example .env
nano .env     # set the live values, then save
php artisan key:generate
```

Key values for production:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://societynew.in
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=societynew
DB_USERNAME=societynew
DB_PASSWORD=********
```

### 3. Install the GitHub Actions runner

On GitHub: **Repo → Settings → Actions → Runners → New self-hosted runner → Linux x64**.
Run the commands GitHub shows, but add the **`production` label**:

```bash
mkdir -p ~/actions-runner && cd ~/actions-runner
curl -o actions-runner.tar.gz -L <DOWNLOAD_URL_FROM_GITHUB>
tar xzf actions-runner.tar.gz
./config.sh \
  --url https://github.com/easylogicstechnology/easylogics-laravel \
  --token <TOKEN_FROM_GITHUB> \
  --labels production \
  --name societynew-prod \
  --unattended

# Run it as a service so it survives reboots:
sudo ./svc.sh install
sudo ./svc.sh start
sudo ./svc.sh status
```

> The runner must have PHP 8.4, Composer and git on its `PATH` (it runs as the site
> user, so whatever that user can run in a shell is what the deploy gets).

### 4. Create the GitHub `production` environment

**Repo → Settings → Environments → New environment → `production`**, then add these
**Environment variables** (not secrets — they are not sensitive):

| Variable         | Value |
|------------------|-------|
| `DEPLOY_PATH`    | `/home/societynew1/htdocs/societynew.in/easylogics-laravel` |
| `SITE_URL`       | `https://societynew.in` |
| `RUN_MIGRATIONS` | `1` to run `php artisan migrate --force` each deploy, else `0` |

Optional: add a **Required reviewer** on this environment so every production deploy
waits for one approval click.

(Repeat for a `testing` environment + a runner labelled `testing` if/when a test
server exists.)

---

## Deploying

- **Automatic:** push/merge into the `production` branch → deploy runs on the live
  server. `main` is the integration branch; cut a release by merging `main` into
  `production` and pushing.

  ```bash
  git checkout production
  git merge main
  git push origin production
  ```

- **Manual:** **Repo → Actions → Deploy → Run workflow → target: production**.

### What a deploy does (`deploy.sh`)

1. Snapshots any uncommitted server edits to `../deploy-backups/`.
2. `git fetch` + `git reset --hard origin/production`.
3. `php artisan down` (maintenance mode; brought back up even on failure).
4. `composer install --no-dev --optimize-autoloader`.
5. Fixes `storage/` + `bootstrap/cache` permissions, `storage:link`.
6. `php artisan migrate --force` *only if* `RUN_MIGRATIONS=1`.
7. Rebuilds config/route/view caches.
8. Health check: `GET $SITE_URL` must return HTTP 200.

---

## Security notes

- **Rotate `APP_KEY`.** The repo is public and `.env` was committed in history, so the
  old key/secrets are exposed. `php artisan key:generate` on the server (step 2) fixes
  the key. Rotate the DB and mail passwords too if they were ever in a committed `.env`.
- **Consider making the repo private.** A public repo with a self-hosted runner can let
  workflows from forked pull requests run on your server. This workflow only triggers on
  `push` (not `pull_request`) and is environment-gated, which limits it — but private is
  safer.
- **`.env` stays out of git.** It is gitignored and created once per server by hand.

---

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Deployment stuck on **"queued"** | No online runner with the matching label. Check `sudo ./svc.sh status` on the server, and that the runner's label is `production`. |
| `DEPLOY_PATH is not set` | Add `DEPLOY_PATH` to the environment (step 4). |
| `... is not a git checkout` | The deploy path isn't a clone. Redo step 1. |
| `.env is missing` | Create it on the server (step 2). |
| Health check fails | Open `SITE_URL` in a browser; check `storage/logs/laravel.log`. The site is auto-brought back up (`php artisan up`) regardless. |
