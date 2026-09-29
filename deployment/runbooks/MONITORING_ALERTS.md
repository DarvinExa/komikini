# Komikini Monitoring & Alerts Plan

Dokumen strategi pemantauan (observability), integrasi Sentry, structured logging, dan penetapan batas pemicu peringatan (alert thresholds) untuk sistem Komikini.

---

## 1. Arsitektur Telemetri & Logging

1. **Structured Request Logging:**
   - Seluruh request HTTP dicatat dengan context terstruktur: `correlation_id`, `route`, `method`, `path`, `status`, `duration_ms`, `ip`, `user_id`, `role`, dan `user_agent`.
   - Data sensitif (**password**, **reset token**, **session id**, **authorization header**, **cookie**, atau **isi komentar**) **dilarang dicatat ke log**.
   - Saluran log terstruktur dapat diarahkan ke berkas JSON harian (`storage/logs/structured.log`) atau agregator log eksternal (Papertrail, Datadog, Grafana Loki).

2. **Error Tracking (Sentry):**
   - Diatur melalui `config/sentry.php`.
   - Diaktifkan ketika `SENTRY_LARAVEL_DSN` diisi di berkas `.env`.
   - Menangkap seluruh unhandled exception dengan metadata correlation ID dan tracing sample rate yang aman tanpa membocorkan SQL bindings sensitif.

---

## 2. Health & Readiness Probes

| Endpoint | Kegunaan | Akses | Respon Sukses | Respon Gagal |
|---|---|---|---|---|
| `GET /up` | Process liveness probe | Publik / Edge balancer | HTTP 200 | HTTP 500 / Down |
| `GET /health/ready` | Kesiapan DB, Cache Redis, Storage | Internal probe / Uptime bot | HTTP 200 `{"status": "ok"}` | HTTP 503 `{"status": "degraded"}` |
| `GET /health/upstream` | Monitor konektivitas comic upstream | Internal monitoring | HTTP 200 `{"status": "ok"}` | HTTP 503 `{"status": "degraded"}` |

> **Prinsip Keamanan Health Endpoint:**
> Endpoint publik tidak mengekspos detail koneksi, nama host internal, atau credential. Detail rincian layanan hanya dikembalikan apabila request menyertakan header otorisasi rahasia `X-Health-Key: <HEALTH_CHECK_SECRET>` atau diakses oleh pengguna bertaraf superadmin.

---

## 3. Ambang Batas Peringatan Awal (Alert Thresholds)

Sesuai spesifikasi `OBSERVABILITY.md`, alarm wajib disetel pada sistem monitoring (Uptime Kuma, Grafana, Datadog, atau Sentry Alert):

| Metrik | Pemicu Peringatan (Trigger Threshold) | Tingkat Urgensi | Saluran Notifikasi |
|---|---|---|---|
| **HTTP 5xx Error Rate** | Error 5xx > 2% dari total request selama 5 menit | **Critical (P1)** | Telegram / PagerDuty / On-call SMS |
| **Upstream Failure** | Upstream API error/timeout > 30% selama 10 menit | **High (P2)** | Telegram Bot / Slack #ops |
| **Queue Latency** | Umur antrean pekerjaan tertua (oldest job) > 5 menit | **High (P2)** | Slack #ops / Email Admin |
| **Failed Queue Jobs** | Terdapat > 5 failed jobs dalam 10 menit | **Medium (P3)** | Slack #ops |
| **Kapasitas Disk VPS** | Penggunaan disk storage > 80% | **High (P2)** | Telegram Bot / Email Admin |
| **Kegagalan Backup** | Skrip `backup.sh` mengembalikan status non-zero | **High (P2)** | Email Superadmin / Telegram Bot |
| **Ranking Refresh Macet** | Tidak ada pembaruan agregasi ranking selama > 2 jadwal (2 jam) | **Medium (P3)** | Slack #ops |
| **Spike Brute-Force / Abuse** | Lonjakan login gagal / rate-limit hit > 50 kejadian / 5 menit | **Medium (P3)** | Slack #security |

---

## 4. Perintah Diagnostik Mandiri Operator (CLI Checklist)

Jika sistem mendeteksi anomali, jalankan rangkaian diagnostik cepat berikut di server:

```bash
# 1. Cek kesehatan internal
curl -i http://127.0.0.1/health/ready

# 2. Cek status worker dan scheduler supervisor
sudo supervisorctl status

# 3. Cek antrean pekerjaan yang tertahan / gagal
php /var/www/komikini/current/artisan queue:monitor default,analytics,notifications
php /var/www/komikini/current/artisan queue:failed

# 4. Cek log error terkini Nginx dan PHP-FPM
sudo tail -n 50 /var/log/nginx/komikini_error.log
sudo tail -n 50 /var/log/php8.3-fpm-komikini.log

# 5. Cek konsumsi memori dan disk
free -h
df -h /
```
