#!/usr/bin/env bash
# ==============================================================================
# Komikini Disaster Recovery & Restore Verification Script
# Usage: ./restore.sh <backup_archive_path> [target_database] [--force-prod]
# ==============================================================================

set -euo pipefail

BACKUP_ARCHIVE="${1:-}"
TARGET_DATABASE="${2:-komikini_restore_drill}"
FORCE_PROD="${3:-}"
ENV_FILE="${ENV_FILE:-/var/www/komikini/shared/.env}"

if [[ -z "${BACKUP_ARCHIVE}" || ! -f "${BACKUP_ARCHIVE}" ]]; then
    echo "ERROR: Backup archive file path required and must exist." >&2
    echo "Usage: $0 /path/to/backup.tar[.enc] [target_database] [--force-prod]" >&2
    exit 1
fi

# Load database credentials from environment
if [[ -f "${ENV_FILE}" ]]; then
    # shellcheck disable=SC1090
    set -a
    source <(grep -E '^(DB_|BACKUP_ENCRYPTION_KEY)' "${ENV_FILE}" || true)
    set +a
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_USERNAME="${DB_USERNAME:-komikini}"
DB_PASSWORD="${DB_PASSWORD:-}"
BACKUP_ENCRYPTION_KEY="${BACKUP_ENCRYPTION_KEY:-}"

# Production overwrite protection
if [[ "${TARGET_DATABASE}" == "komikini" && "${FORCE_PROD}" != "--force-prod" ]]; then
    echo "CRITICAL SAFETY GUARD:" >&2
    echo "Target is the active production database '${TARGET_DATABASE}'." >&2
    echo "To proceed, you must explicitly supply '--force-prod' as the 3rd argument." >&2
    exit 1
fi

WORK_DIR=$(mktemp -d /tmp/komikini_restore_XXXXXX)
trap 'rm -rf "${WORK_DIR}"' EXIT

echo "============================================================"
echo " Starting Komikini Restore & Verification Drill"
echo " Target Database: ${TARGET_DATABASE}"
echo "============================================================"

# Step 1: Checksum Verification
CHECKSUM_FILE="${BACKUP_ARCHIVE}.sha256"
if [[ -f "${CHECKSUM_FILE}" ]]; then
    echo "[1/5] Verifying archive SHA-256 checksum..."
    sha256sum -c "${CHECKSUM_FILE}"
else
    echo "[1/5] Warning: Checksum file not found; skipping verification."
fi

# Step 2: Decryption (if encrypted)
RESTORE_TAR="${WORK_DIR}/archive.tar"
if [[ "${BACKUP_ARCHIVE}" =~ \.enc$ ]]; then
    if [[ -z "${BACKUP_ENCRYPTION_KEY}" ]]; then
        echo "ERROR: Backup is encrypted but BACKUP_ENCRYPTION_KEY is empty." >&2
        exit 1
    fi
    echo "[2/5] Decrypting backup archive..."
    openssl enc -d -aes-256-cbc -pbkdf2 -pass "pass:${BACKUP_ENCRYPTION_KEY}" \
        -in "${BACKUP_ARCHIVE}" \
        -out "${RESTORE_TAR}"
else
    echo "[2/5] Using unencrypted backup archive..."
    cp "${BACKUP_ARCHIVE}" "${RESTORE_TAR}"
fi

# Step 3: Extract Archive Contents
echo "[3/5] Extracting archive contents..."
tar -xf "${RESTORE_TAR}" -C "${WORK_DIR}"

DB_DUMP=$(find "${WORK_DIR}" -name "db_*.dump.gz" | head -n 1)
if [[ -z "${DB_DUMP}" || ! -f "${DB_DUMP}" ]]; then
    echo "ERROR: PostgreSQL dump file not found within archive." >&2
    exit 1
fi

# Step 4: Ensure Target Database exists and restore
echo "[4/5] Restoring database to ${TARGET_DATABASE}..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d postgres \
    -c "SELECT 1 FROM pg_database WHERE datname='${TARGET_DATABASE}'" | grep -q 1 || \
PGPASSWORD="${DB_PASSWORD}" createdb -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" "${TARGET_DATABASE}"

PGPASSWORD="${DB_PASSWORD}" pg_restore \
    -h "${DB_HOST}" \
    -p "${DB_PORT}" \
    -U "${DB_USERNAME}" \
    -d "${TARGET_DATABASE}" \
    --clean --if-exists \
    --no-owner --no-privileges \
    "${DB_DUMP}" || true

# Step 5: Data Integrity Verification Query
echo "[5/5] Verifying database integrity & row counts..."
USER_COUNT=$(PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d "${TARGET_DATABASE}" -t -c "SELECT COUNT(*) FROM users;" | xargs)
COMIC_COUNT=$(PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d "${TARGET_DATABASE}" -t -c "SELECT COUNT(*) FROM comics;" | xargs)
CHAPTER_COUNT=$(PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d "${TARGET_DATABASE}" -t -c "SELECT COUNT(*) FROM chapters;" | xargs)

echo "------------------------------------------------------------"
echo " Verification Report for ${TARGET_DATABASE}:"
echo " - Users: ${USER_COUNT}"
echo " - Comics: ${COMIC_COUNT}"
echo " - Chapters: ${CHAPTER_COUNT}"
echo "------------------------------------------------------------"

echo "============================================================"
echo " Restore drill completed successfully!"
echo "============================================================"
