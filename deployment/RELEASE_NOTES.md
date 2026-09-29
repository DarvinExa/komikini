# Komikini Release Notes — Version 1.0.0 (Release Candidate 1)

**Tanggal Rilis:** 29 September 2026  
**Status Kesiapan:** Release Candidate (RC-1)  
**Target Environment:** Staging / Production-ready  

---

## 1. Ringkasan Eksekutif
**Komikini** adalah platform pembaca komik (manga, manhwa, manhua) modern, cepat, dan berkinerja tinggi yang dibangun dengan arsitektur modular di atas Laravel 12, Inertia.js, React 19, TypeScript, PostgreSQL 16, dan Redis 7. Desain antarmuka mengadopsi tema Brutalist Dark (#111111, #161616, #222222, #BAD306, #F8F8F8) yang memenuhi standar aksesibilitas WCAG 2.2 AA.

Rilis `v1.0.0-rc1` menandai penyelesaian seluruh fitur inti (P0), pengerasan keamanan multi-lapis (OWASP ASVS), optimasi SEO/A11y, kesiapan telemetri terstruktur, dan otomatisasi operasional deployment tanpa downtime.

---

## 2. Fitur Utama yang Dihadirkan

### A. Discovery & Pembaca Komik (Catalog & Reader)
- **Beranda & Eksplorasi:** Katalog komik populer, rilisan terbaru, filter berdasarkan genre, dan pencarian instan dengan debounce dan penanganan karakter khusus aman (Anti-XSS/SQLi).
- **Pembaca Gambar Adaptif:** Mode baca scroll vertikal dengan LCP eager loading pada 2 halaman pertama (`fetchpriority="high"`) dan lazy loading pada halaman berikutnya. Dukungan navigasi keyboard (panah kiri/kanan, PageUp/PageDown) dan kontrol bab mengambang responsif.
- **Circuit Fallback Cache:** Bila penyedia data hulu (*upstream*) mengalami *downtime* atau *rate-limiting*, sistem secara anggun menyajikan *stale cache* tanpa mengekspos *stack trace* atau mengganggu pengalaman membaca.

### B. Akun, Pustaka, & Personalisasi (Identity & Library)
- **Autentikasi Aman:** Registrasi, verifikasi email, login, reset password, dan manajemen sesi terlindungi dengan pembatasan percobaan (*rate-limiting*) serta Cloudflare Turnstile adaptif.
- **Pustaka & Riwayat Otomatis:** Penyimpanan kemajuan membaca otomatis berbasis debounce saat pengguna menggulir halaman, dilengkapi fitur penanda (*bookmark*) komik.

### C. Komunitas & Moderasi (Community)
- **Komentar Bertingkat Tunggal:** Pengguna dapat membuat, mengedit, dan menghapus komentar sendiri dengan perlindungan ketat IDOR/BOLA.
- **Sistem Pelaporan & Moderasi:** Pengguna dapat melaporkan komentar pelanggaran; moderator dapat menyembunyikan komentar yang dilaporkan dengan pencatatan jejak audit.

### D. Peringkat Komik Internal (Internal Ranking)
- **Qualified View Ingestion:** Mekanisme pencatatan tayangan valid dengan jendela deduplikasi 6 jam per pengunjung/IP/komik/bab guna mencegah manipulasi bot.
- **Agregasi Periodik:** Job background terjadwal yang mengalkulasi skor popularitas harian, mingguan, bulanan, dan sepanjang masa.

### E. Konsol Administrasi (Admin Console & RBAC)
- **Role-Based Access Control (Spatie):** Matriks hak akses terperinci untuk peran `superadmin`, `admin`, `moderator`, dan `user`. Invarian sistem menjamin minimal satu superadmin aktif tidak dapat dihapus atau diturunkan pangkatnya sendiri.
- **Jejak Audit Terstruktur:** Pencatatan otomatis mutasi peran, moderasi komentar, pembersihan cache, dan penangguhan pengguna.

### F. Keamanan, SEO, & Aksesibilitas
- **Header Keamanan Berlapis:** CSP, HSTS, X-Content-Type-Options nosniff, X-Frame-Options DENY, dan token korelasi `X-Correlation-ID`.
- **SSRF & Image Allowlist Validator:** Pemblokiran alamat IP internal/loopback/cloud metadata (RFC 1918, 169.254.169.254) pada proxy gambar.
- **Aksesibilitas (WCAG 2.2 AA):** Skip-to-content links, kontras rasio tinggi (>7:1), asosiasi `aria-describedby` pada formulir, dan touch target minimum 44×44px.
- **SEO & Indeksasi:** Komponen `SeoHead` dengan Open Graph, Twitter Cards, dynamic `robots.txt`, dan dynamic XML sitemap (`/sitemap.xml`).

### G. Operasional & Otomasi Deployment
- **Health Probes:** `/up` (liveness publik) dan `/health/ready` (readiness internal untuk PostgreSQL, Redis, dan storage dengan proteksi rahasia token).
- **Skrip Deployment Atomic:** Skrip zero-downtime `deploy.sh` berbasis symlink release dan rollback instan otomatis `rollback.sh`.
- **Backup & Restore Terenkripsi:** Skrip `backup.sh` dan `restore.sh` dengan enkripsi AES-256-CBC, verifikasi checksum SHA-256, dan retensi 7h/4m/3b.

---

## 3. Spesifikasi Teknis Minimum
- **OS Target:** Ubuntu 24.04 LTS (2 vCPU, 4 GB RAM, SSD 40+ GB)
- **PHP:** PHP 8.3-FPM (ekstensi: `pdo_pgsql`, `redis`, `mbstring`, `xml`, `curl`, `bcmath`, `intl`, `gd`, `zip`)
- **Database:** PostgreSQL 16 (bind 127.0.0.1)
- **Cache/Queue:** Redis 7 (bind 127.0.0.1)
- **Web Server:** Nginx 1.24+ dengan sertifikat SSL Cloudflare Origin CA & Authenticated Origin Pulls (AOP)
- **Supervision:** Supervisor 4.2+ (worker antrean & scheduler)

---

## 4. Item Backlog Non-Blocker (Rilis Mendatang)
1. Penambahan paket `sentry/sentry-laravel` ke dependensi produksi saat DSN monitoring aktif.
2. Partisi sitemap bertingkat (`<sitemapindex>`) apabila katalog komik melampaui 10.000 judul.
3. Fitur navigasi gestur swipe horizontal antar-bab pada perangkat mobile layar sentuh.
