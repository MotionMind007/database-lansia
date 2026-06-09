#!/bin/bash
set -e

# ═══════════════════════════════════════════════════════
# Zero-downtime deploy script for Lansia Papua
# ═══════════════════════════════════════════════════════
#
# Usage: ./deploy/scripts/deploy.sh [branch]
# Default branch: main
#
# Prerequisites:
# - Server has PHP 8.3+, Composer, Node.js 20+, npm
# - /var/www/lansia-papua is the deployment root
# - Supervisor manages queue workers
# - Nginx points to /var/www/lansia-papua/current/public
#
# Directory structure:
# /var/www/lansia-papua/
# ├── releases/           # timestamped release dirs
# ├── current -> releases/YYYYMMDD_HHMMSS  # symlink
# ├── shared/
# │   ├── .env
# │   └── storage/
# └── repo/               # bare git clone

DEPLOY_ROOT="/var/www/lansia-papua"
REPO_DIR="${DEPLOY_ROOT}/repo"
SHARED_DIR="${DEPLOY_ROOT}/shared"
RELEASES_DIR="${DEPLOY_ROOT}/releases"
BRANCH="${1:-main}"
KEEP_RELEASES=5

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RELEASE_DIR="${RELEASES_DIR}/${TIMESTAMP}"

echo "═══════════════════════════════════════════"
echo " Deploying Lansia Papua (branch: ${BRANCH})"
echo " Release: ${TIMESTAMP}"
echo "═══════════════════════════════════════════"

# Step 1: Pull latest code
echo "[1/9] Pulling latest code..."
cd "${REPO_DIR}"
git fetch origin "${BRANCH}"
git reset --hard "origin/${BRANCH}"

# Step 2: Create release directory
echo "[2/9] Creating release directory..."
mkdir -p "${RELEASE_DIR}"
git archive "origin/${BRANCH}" | tar -x -C "${RELEASE_DIR}"

# Step 3: Link shared resources
echo "[3/9] Linking shared resources..."
ln -nfs "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
rm -rf "${RELEASE_DIR}/storage"
ln -nfs "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"

# Step 4: Install dependencies
echo "[4/9] Installing Composer dependencies..."
cd "${RELEASE_DIR}"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "[4/9] Installing NPM dependencies and building assets..."
npm ci --ignore-scripts
npm run build
rm -rf node_modules

# Step 5: Run migrations
echo "[5/9] Running database migrations..."
php artisan migrate --force

# Step 6: Optimize for production
echo "[6/9] Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Step 7: Swap symlink (atomic)
echo "[7/9] Swapping symlink (zero-downtime)..."
ln -nfs "${RELEASE_DIR}" "${DEPLOY_ROOT}/current_new"
mv -Tf "${DEPLOY_ROOT}/current_new" "${DEPLOY_ROOT}/current"

# Step 8: Restart workers
echo "[8/9] Restarting queue workers..."
php artisan queue:restart

# Reload PHP-FPM to pick up OPcache changes
if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.3-fpm 2>/dev/null || true
fi

# Step 9: Cleanup old releases
echo "[9/9] Cleaning up old releases (keeping ${KEEP_RELEASES})..."
cd "${RELEASES_DIR}"
ls -dt */ | tail -n +$((KEEP_RELEASES + 1)) | xargs rm -rf 2>/dev/null || true

echo ""
echo "═══════════════════════════════════════════"
echo " Deploy complete! Release: ${TIMESTAMP}"
echo "═══════════════════════════════════════════"
echo ""
echo "Post-deploy checks:"
echo "  php artisan app:production-status"
echo "  php artisan dashboard:health"
echo "  curl -s http://localhost/health | jq ."
