#!/usr/bin/env bash
# ==============================================================================
# Komikini Zero-Downtime Atomic Deployment Script
# Usage: ./deploy.sh [branch_or_commit]
# ==============================================================================

set -euo pipefail

APP_NAME="komikini"
BASE_DIR="/var/www/${APP_NAME}"
RELEASES_DIR="${BASE_DIR}/releases"
SHARED_DIR="${BASE_DIR}/shared"
CURRENT_LINK="${BASE_DIR}/current"
TIMESTAMP=$(date -u +"%Y%m%d%H%M%S")
RELEASE_DIR="${RELEASES_DIR}/${TIMESTAMP}"
BRANCH="${1:-main}"

echo "============================================================"
echo " Starting Komikini Deployment: Release ${TIMESTAMP}"
echo "============================================================"

# Step 1: Verify directories & shared prerequisites
if [[ ! -d "${SHARED_DIR}" ]]; then
    echo "ERROR: Shared directory ${SHARED_DIR} does not exist." >&2
    exit 1
fi

if [[ ! -f "${SHARED_DIR}/.env" ]]; then
    echo "ERROR: Shared configuration ${SHARED_DIR}/.env does not exist." >&2
    exit 1
fi

mkdir -p "${RELEASES_DIR}"
mkdir -p "${SHARED_DIR}/storage/app/public"
mkdir -p "${SHARED_DIR}/storage/framework/cache"
mkdir -p "${SHARED_DIR}/storage/framework/sessions"
mkdir -p "${SHARED_DIR}/storage/framework/views"
mkdir -p "${SHARED_DIR}/storage/logs"

# Step 2: Clone or export source code
echo "[1/8] Cloning application repository (${BRANCH})..."
git clone --depth 1 --branch "${BRANCH}" "file:///var/repo/${APP_NAME}.git" "${RELEASE_DIR}" || \
git clone --depth 1 --branch "${BRANCH}" "https://github.com/example/${APP_NAME}.git" "${RELEASE_DIR}"

cd "${RELEASE_DIR}"

# Step 3: Link shared storage and configuration
echo "[2/8] Linking shared environment and storage..."
rm -rf "${RELEASE_DIR}/storage"
ln -sfn "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"
ln -sf "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"

# Step 4: Install PHP dependencies (no-dev, optimized)
echo "[3/8] Installing Composer dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet

# Step 5: Install and build frontend assets
echo "[4/8] Building frontend assets..."
if command -v npm &> /dev/null; then
    npm ci --silent
    npm run build --silent
else
    echo "Note: npm not found on target host; using pre-built CI artifacts."
fi

# Step 6: Run database migrations
echo "[5/8] Running database migrations..."
php artisan migrate --force

# Step 7: Warm up framework caches
echo "[6/8] Optimizing application caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Step 8: Atomic symlink switch
echo "[7/8] Activating new release..."
ln -sfn "${RELEASE_DIR}" "${CURRENT_LINK}"

# Restart queues and reload PHP-FPM
php "${CURRENT_LINK}/artisan" queue:restart
if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.3-fpm || sudo systemctl reload php-fpm || true
    sudo supervisorctl restart all || true
fi

# Step 9: Post-deployment Health Check & Automated Rollback
echo "[8/8] Performing health check..."
sleep 2
HEALTH_URL="http://127.0.0.1/health/ready"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${HEALTH_URL}" || echo "000")

if [[ "${HTTP_STATUS}" != "200" ]]; then
    echo "CRITICAL: Health check failed with status ${HTTP_STATUS}!" >&2
    echo "Initiating automatic rollback to previous release..." >&2
    /bin/bash "${BASE_DIR}/deployment/scripts/rollback.sh"
    exit 1
fi

# Step 10: Prune old releases (keep latest 5)
echo "Pruning older releases (retaining 5 most recent)..."
cd "${RELEASES_DIR}"
ls -dt */ | tail -n +6 | xargs -r rm -rf

echo "============================================================"
echo " Komikini Deployment Completed Successfully!"
echo " Current Release: ${TIMESTAMP} -> ${RELEASE_DIR}"
echo "============================================================"
