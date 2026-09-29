# Komikini Rollback Runbook

Panduan mitigasi dan prosedur rollback darurat ketika rilis baru aplikasi mengalami kegagalan, degradasi performa, atau error kritis di lingkungan production.

---

## 1. Kriteria Pemicu Rollback
Rollback harus segera dipicu apabila salah satu kondisi berikut terdeteksi dalam 15 menit pasca-deployment:
1. **Lonjakan HTTP 5xx:** Rasio error 5xx melebihi 2% dari total traffic selama 3-5 menit berturut-turut.
2. **Health Check Gagal:** Endpoint `/health/ready` mengembalikan kode status non-200.
3. **Queue Failure:** Pekerjaan antrean kritis mengalami kegagalan berulang (`failed_jobs` melonjak).
4. **Data Corruption / Migration Regression:** Terjadi kegagalan fungsional berat pada alur pembaca komik atau login pengguna.

---

## 2. Prosedur Rollback Instan Aplikasi (Zero-Downtime)

Aplikasi Komikini menggunakan rilis berbasis symlink `/var/www/komikini/current`. Rollback aplikasi dapat dilakukan dalam hitungan detik tanpa perlu mengunduh ulang kode:

### Langkah 2.1: Eksekusi Skrip Rollback
```bash
cd /var/www/komikini/deployment/scripts
sudo -u www-data ./rollback.sh
```

Skrip ini akan secara otomatis:
1. Mengidentifikasi rilis aktif dan rilis stabil sebelumnya di `/var/www/komikini/releases/`.
2. Mengalihkan symlink `/var/www/komikini/current` ke rilis stabil sebelumnya secara atomic.
3. Memanaskan ulang konfigurasi dan rute cache untuk rilis sebelumnya.
4. Merestart worker antrean (`php artisan queue:restart`) secara graceful.
5. Mereload worker PHP-FPM.
6. Memverifikasi status kesehatan pada `/health/ready`.

### Langkah 2.2: Rollback ke Rilis Tertentu (Spesifik)
Jika ingin memutar kembali ke timestamp rilis tertentu:
```bash
sudo -u www-data ./rollback.sh 20260929110000
```

---

## 3. Matriks Keputusan Rollback Database

Prinsip utama Komikini: **Utamakan migrasi yang backward-compatible**.

| Skenario Migrasi | Tindakan Rekomendasi |
|---|---|
| Penambahan kolom baru nullable / tabel baru | **Biarkan skema database tetap pada versi baru.** Kode rilis lama tetap dapat berjalan normal tanpa perlu rollback skema. |
| Perubahan logika/indeks | Evaluasi apakah kode rilis lama terpengaruh. Jika tidak terpengaruh, jangan rollback database. |
| Migrasi destruktif / breaking change | 1. Masuk ke mode maintenance: `php artisan down --secret="emergency-bypass"`<br>2. Jalankan rollback migrasi: `php artisan migrate:rollback --step=1`<br>3. Alihkan symlink aplikasi.<br>4. Nonaktifkan mode maintenance: `php artisan up` |

---

## 4. Verifikasi Pasca-Rollback
Setelah rollback selesai, operator wajib memverifikasi:
- [ ] Endpoint `/up` dan `/health/ready` berstatus HTTP 200 `{"status": "ok"}`.
- [ ] Log Nginx (`/var/log/nginx/komikini_error.log`) dan Laravel (`storage/logs/laravel.log`) bersih dari fatal exception.
- [ ] Worker antrean aktif memproses job: `sudo supervisorctl status`.
- [ ] Uji alur kritis pengguna: Homepage, detail komik, reader, dan login.
