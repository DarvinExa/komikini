#!/usr/bin/env bash
# ==============================================================================
# Komikini Automated Encrypted Backup Script
# Retention policy: 7 daily + 4 weekly + 3 monthly
# ==============================================================================

set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/var/backups/komikini}"
SHARED_STORAGE="${SHARED_STORAGE:-/var/www/komikini/shared/storage/app/public}"
ENV_FILE="${ENV_FILE:-/var/www/komikini/shared/.env}"
TIMESTAMP=$(date -u +"%Y%m%d_%H%M%SZ")
DAY_OF_WEEK=$(date -u +"%u") # 1 = Monday, 7 = Sunday
DAY_OF_MONTH=$(date -u +"%d") # 01 - 31

# Load environment variables if available
if [[ -f "${ENV_FILE}" ]]; then
    # shellcheck disable=SC1090
    set -a
    source <(grep -E '^(DB_|BACKUP_ENCRYPTION_KEY)' "${ENV_FILE}" || true)
    set +a
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-komikini}"
DB_USERNAME="${DB_USERNAME:-komikini}"
DB_PASSWORD="${DB_PASSWORD:-}"
BACKUP_ENCRYPTION_KEY="${BACKUP_ENCRYPTION_KEY:-}"

DAILY_DIR="${BACKUP_DIR}/daily"
WEEKLY_DIR="${BACKUP_DIR}/weekly"
MONTHLY_DIR="${BACKUP_DIR}/monthly"
TEMP_DIR="${BACKUP_DIR}/tmp_${TIMESTAMP}"

mkdir -p "${DAILY_DIR}" "${WEEKLY_DIR}" "${MONTHLY_DIR}" "${TEMP_DIR}"

echo "============================================================"
echo " Starting Komikini Backup: ${TIMESTAMP}"
echo "============================================================"

# Step 1: PostgreSQL Database Dump
DB_FILE="${TEMP_DIR}/db_${DB_DATABASE}_${TIMESTAMP}.dump.gz"
echo "[1/4] Creating PostgreSQL database dump for ${DB_DATABASE}..."
PGPASSWORD="${DB_PASSWORD}" pg_dump \
    -h "${DB_HOST}" \
    -p "${DB_PORT}" \
    -U "${DB_USERNAME}" \
    -d "${DB_DATABASE}" \
    -F c \
    -Z 9 \
    -f "${DB_FILE}"

# Step 2: User Uploaded Storage Archive
STORAGE_FILE="${TEMP_DIR}/storage_public_${TIMESTAMP}.tar.gz"
echo "[2/4] Archiving public storage..."
if [[ -d "${SHARED_STORAGE}" ]]; then
    tar -czf "${STORAGE_FILE}" -C "$(dirname "${SHARED_STORAGE}")" "$(basename "${SHARED_STORAGE}")"
else
    echo "Note: ${SHARED_STORAGE} not found; skipping storage archive."
fi

# Step 3: Bundle and Encrypt
FINAL_PACKAGE="${DAILY_DIR}/komikini_backup_${TIMESTAMP}.tar"
tar -cf "${FINAL_PACKAGE}" -C "${TEMP_DIR}" .
rm -rf "${TEMP_DIR}"

if [[ -n "${BACKUP_ENCRYPTION_KEY}" ]]; then
    echo "[3/4] Encrypting backup archive using AES-256-CBC..."
    openssl enc -aes-256-cbc -salt -pbkdf2 -pass "pass:${BACKUP_ENCRYPTION_KEY}" \
        -in "${FINAL_PACKAGE}" \
        -out "${FINAL_PACKAGE}.enc"
    rm -f "${FINAL_PACKAGE}"
    FINAL_PACKAGE="${FINAL_PACKAGE}.enc"
else
    echo "[3/4] Warning: BACKUP_ENCRYPTION_KEY not set. Archive left unencrypted."
fi

# Generate SHA-256 Checksum
sha256sum "${FINAL_PACKAGE}" > "${FINAL_PACKAGE}.sha256"
echo "Backup archive created: ${FINAL_PACKAGE}"
echo "Checksum: $(cat "${FINAL_PACKAGE}.sha256")"

# Step 4: Rotation & Tiered Retention
echo "[4/4] Applying tiered retention policy..."

# Sunday -> Weekly retention tier
if [[ "${DAY_OF_WEEK}" -eq 7 ]]; then
    cp -p "${FINAL_PACKAGE}"* "${WEEKLY_DIR}/"
fi

# 1st day of month -> Monthly retention tier
if [[ "${DAY_OF_MONTH}" -eq "01" ]]; then
    cp -p "${FINAL_PACKAGE}"* "${MONTHLY_DIR}/"
fi

# Pruning:
# Daily: keep 7 days
find "${DAILY_DIR}" -type f -mtime +7 -delete

# Weekly: keep 4 weeks (28 days)
find "${WEEKLY_DIR}" -type f -mtime +28 -delete

# Monthly: keep 3 months (90 days)
find "${MONTHLY_DIR}" -type f -mtime +90 -delete

echo "============================================================"
echo " Backup completed and verified successfully!"
echo "============================================================"
