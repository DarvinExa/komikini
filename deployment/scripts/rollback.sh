#!/usr/bin/env bash
# ==============================================================================
# Komikini Instant Atomic Rollback Script
# Usage: ./rollback.sh [target_release_timestamp]
# ==============================================================================

set -euo pipefail

APP_NAME="komikini"
BASE_DIR="/var/www/${APP_NAME}"
RELEASES_DIR="${BASE_DIR}/releases"
CURRENT_LINK="${BASE_DIR}/current"

echo "============================================================"
echo " Initiating Komikini Application Rollback"
echo "============================================================"

if [[ ! -L "${CURRENT_LINK}" ]]; then
    echo "ERROR: Current release symlink ${CURRENT_LINK} not found." >&2
    exit 1
fi

CURRENT_RELEASE=$(readlink -f "${CURRENT_LINK}")
echo "Currently active release: ${CURRENT_RELEASE}"

TARGET_RELEASE=""

if [[ $# -ge 1 && -n "$1" ]]; then
    if [[ -d "${RELEASES_DIR}/$1" ]]; then
        TARGET_RELEASE="${RELEASES_DIR}/$1"
    else
        echo "ERROR: Specified release ${RELEASES_DIR}/$1 does not exist." >&2
        exit 1
    fi
else
    # Find previous release from directory list sorted by modification time
    cd "${RELEASES_DIR}"
    PREVIOUS_RELEASES=$(ls -dt */ | tr -d '/')

    for rel in ${PREVIOUS_RELEASES}; do
        FULL_PATH="${RELEASES_DIR}/${rel}"
        if [[ "${FULL_PATH}" != "${CURRENT_RELEASE}" && -d "${FULL_PATH}" ]]; then
            TARGET_RELEASE="${FULL_PATH}"
            break
        fi
    done
fi

if [[ -z "${TARGET_RELEASE}" ]]; then
    echo "ERROR: No previous release found to rollback to." >&2
    exit 1
fi

echo "Rolling back to target release: ${TARGET_RELEASE}"

# Step 1: Atomic switch symlink
ln -sfn "${TARGET_RELEASE}" "${CURRENT_LINK}"

# Step 2: Refresh caches on target release
cd "${CURRENT_LINK}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Step 3: Gracefully restart queue workers and reload services
php "${CURRENT_LINK}/artisan" queue:restart

if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.3-fpm || sudo systemctl reload php-fpm || true
    sudo supervisorctl restart all || true
fi

# Step 4: Verify health
sleep 1
HEALTH_URL="http://127.0.0.1/health/ready"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${HEALTH_URL}" || echo "000")

if [[ "${HTTP_STATUS}" == "200" ]]; then
    echo "============================================================"
    echo " Rollback completed successfully! Active release: ${TARGET_RELEASE}"
    echo "============================================================"
else
    echo "WARNING: Rollback symlink switched, but health check returned status ${HTTP_STATUS}." >&2
    echo "Please inspect application logs immediately: /var/log/nginx/ and storage/logs/" >&2
fi
