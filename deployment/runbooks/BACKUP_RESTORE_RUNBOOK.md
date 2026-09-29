# Komikini Backup & Restore Runbook

Panduan operasional pencadangan (backup), enkripsi data, kebijakan retensi, dan simulasi pemulihan (restore drill) platform Komikini.

---

## 1. Kebijakan Retensi Backup
Sesuai `DEVOPS.md`, kebijakan retensi berjenjang yang diterapkan adalah:
- **Harian (Daily):** Disimpan selama 7 hari.
- **Mingguan (Weekly):** Diambil setiap hari Minggu, disimpan selama 4 minggu (28 hari).
- **Bulanan (Monthly):** Diambil pada tanggal 1 setiap bulan, disimpan selama 3 bulan (90 hari).

Komponen yang dicadangkan:
1. **Database PostgreSQL:** Seluruh skema, tabel, indeks, dan sequence.
2. **Storage Uploads:** Aset unggahan pengguna (`storage/app/public/avatars`, dan media lainnya).
3. **Integritas:** Checksum SHA-256 dibuat otomatis untuk setiap berkas arsip.
4. **Keamanan:** Enkripsi simetris menggunakan AES-256-CBC dengan PBKDF2.

---

## 2. Otomasi Penjadwalan Backup
Skrip `deployment/scripts/backup.sh` dijadwalkan berjalan otomatis setiap hari pukul 02:00 UTC melalui crontab pengguna `www-data`:

```bash
# Tambahkan ke /etc/cron.d/komikini-backup
0 2 * * * www-data BACKUP_ENCRYPTION_KEY="your_secure_passphrase" /var/www/komikini/deployment/scripts/backup.sh >> /var/log/komikini_backup.log 2>&1
```

### Eksekusi Manual Backup
```bash
sudo -u www-data BACKUP_ENCRYPTION_KEY="your_secure_passphrase" /var/www/komikini/deployment/scripts/backup.sh
```

---

## 3. Replikasi Offsite
Berkas backup yang dihasilkan di `/var/backups/komikini/` wajib disinkronkan ke lokasi offsite (misalnya bucket Cloudflare R2 atau AWS S3) menggunakan `rclone` atau `awscli`:

```bash
# Contoh sinkronisasi harian ke bucket offsite
rclone sync /var/backups/komikini/ remote-s3:komikini-backups/ \
    --exclude "tmp_*/**" \
    --fast-list \
    --log-file=/var/log/rclone_backup.log
```

---

## 4. Prosedur Restore Drill (Simulasi Pemulihan)
Restore drill wajib dilakukan secara berkala (minimal triwulanan dan sebelum rilis besar):

### Langkah 4.1: Simulasi pada Database Uji/Staging
Gunakan target database terisolasi (misalnya `komikini_restore_drill`):

```bash
# Eksekusi restore drill
sudo -u postgres BACKUP_ENCRYPTION_KEY="your_secure_passphrase" \
    /var/www/komikini/deployment/scripts/restore.sh \
    /var/backups/komikini/daily/komikini_backup_YYYYMMDD_HHMMSSZ.tar.enc \
    komikini_restore_drill
```

Skrip akan otomatis:
1. Memverifikasi berkas checksum SHA-256.
2. Mendekripsi arsip terenkripsi.
3. Mengekstrak dump database dan folder upload.
4. Melakukan `pg_restore` ke database target.
5. Menampilkan laporan jumlah baris tabel kunci (`users`, `comics`, `chapters`).

### Langkah 4.2: Pemulihan Penuh ke Database Production (Emergency DR)
Jika terjadi insiden fatal dan database production harus dipulihkan:
```bash
sudo -u postgres BACKUP_ENCRYPTION_KEY="your_secure_passphrase" \
    /var/www/komikini/deployment/scripts/restore.sh \
    /var/backups/komikini/daily/komikini_backup_YYYYMMDD_HHMMSSZ.tar.enc \
    komikini \
    --force-prod
```
*Catatan: Argumen `--force-prod` wajib disertakan sebagai pengaman terhadap penimpaan database utama.*
