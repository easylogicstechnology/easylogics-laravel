#!/usr/bin/env bash
#
# Runs ON THE SERVER from .github/workflows/deploy.yml (self-hosted runner),
# as the site user. Can also be run by hand:
#   bash .github/scripts/deploy.sh /home/<user>/htdocs/<domain> main
# Set RUN_MIGRATIONS=1 to also run `php artisan migrate --force`.
#
set -euo pipefail

DEPLOY_PATH="${1:?deploy path required}"
BRANCH="${2:?branch required}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-0}"

cd "$DEPLOY_PATH"

if [ ! -d .git ]; then
	echo "ERROR: $DEPLOY_PATH is not a git checkout. Do the one-time setup in docs/DEPLOYMENT.md."
	exit 1
fi

# .env holds the DB and mail credentials and is gitignored. Without it the
# site cannot boot - fail here, not in the browser.
if [ ! -f .env ]; then
	echo "ERROR: .env is missing in $DEPLOY_PATH (gitignored by design). Create it from .env.example."
	exit 1
fi

# Snapshot edits made directly on the server before the hard reset throws them away.
CHANGED_FILES="$(git diff HEAD --ignore-cr-at-eol --name-only)"
if [ -n "$CHANGED_FILES" ]; then
	mkdir -p ../deploy-backups
	PATCH="../deploy-backups/server-edits-$(date +%Y%m%d-%H%M%S).patch"
	echo "$CHANGED_FILES" | xargs -d '\n' git diff HEAD -- > "$PATCH"
	echo "WARNING: server had uncommitted edits to tracked files:"
	echo "$CHANGED_FILES" | sed 's/^/           /'
	echo "         Saved to $PATCH (apply with: git apply <patch>)"
fi

# NEVER add `git clean`: storage/app (uploads) and .env are untracked live data.
git fetch --prune origin
git reset --hard "origin/$BRANCH"

# Always bring the site back up, even if a step below fails.
php artisan down --retry=60 || true
trap 'php artisan up || true' EXIT

composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

[ -e public/storage ] || php artisan storage:link

if [ "$RUN_MIGRATIONS" = "1" ]; then
	php artisan migrate --force
else
	echo "Skipping migrations (RUN_MIGRATIONS is not 1)."
fi


php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
echo "Deployed $(git rev-parse --short HEAD) ($BRANCH) to $DEPLOY_PATH"
