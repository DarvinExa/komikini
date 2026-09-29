# Komikini Deployment Runbook

Dokumen ini memandu langkah provisioning, deployment, dan pemeliharaan server Komikini di lingkungan **Staging** dan **Production**.

---

## 1. Spesifikasi Server Baseline (VPS)
- **OS:** Ubuntu 24.04 LTS (Noble Numbat)
- **Hardware Minimum:** 2 vCPU, 4 GB RAM, SSD 40+ GB
- **Software Stack:**
  - Nginx 1.24+
  - PHP 8.3-FPM (`php8.3-cli`, `php8.3-fpm`, `php8.3-pgsql`, `php8.3-redis`, `php8.3-mbstring`, `php8.3-xml`, `php8.3-curl`, `php8.3-bcmath`, `php8.3-intl`, `php8.3-gd`, `php8.3-zip`)
  - PostgreSQL 16
  - Redis 7
  - Supervisor
  - Composer 2.7+ & Node.js 22 LTS (jika build dilakukan di server)

---

## 2. Struktur Direktori Deployment
Aplikasi menggunakan pola deployment atomic tanpa downtime berbasis symlink:

```text
/var/www/komikini/
├── current -> /var/www/komikini/releases/20260929120000   (Symlink ke release aktif)
├── shared/
│   ├── .env                                              (Environment configuration)
│   └── storage/
│       ├── app/public/                                   (User uploaded assets)
│       ├── framework/cache/
│       ├── framework/sessions/
│       ├── framework/views/
│       └── logs/                                         (Application logs)
├── releases/
│   ├── 20260929110000/
│   └── 20260929120000/
└── deployment/
    ├── nginx/
    ├── php/
    ├── supervisor/
    └── scripts/
```

---

## 3. Langkah Inisialisasi Server Baru

### Langkah 3.1: Install Dependensi OS
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl git ufw supervisor \
    nginx postgresql postgresql-contrib redis-server

# Repository PHP Ondrej
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-pgsql php8.3-redis \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-intl php8.3-gd php8.3-zip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Langkah 3.2: Buat Direktori dan Permission
```bash
sudo mkdir -p /var/www/komikini/{releases,shared/storage/app/public,shared/storage/framework/{cache,sessions,views},shared/storage/logs}
sudo chown -R www-data:www-data /var/www/komikini
sudo chmod -R 775 /var/www/komikini/shared/storage
```

### Langkah 3.3: Konfigurasi Database & Redis
```bash
# PostgreSQL
sudo -u postgres psql -c "CREATE USER komikini WITH PASSWORD 'YOUR_STRONG_PASSWORD';"
sudo -u postgres psql -c "CREATE DATABASE komikini OWNER komikini;"

# Pastikan PostgreSQL hanya mendengarkan localhost:
# /etc/postgresql/16/main/postgresql.conf -> listen_addresses = 'localhost'

# Pastikan Redis hanya mendengarkan localhost:
# /etc/redis/redis.conf -> bind 127.0.0.1 ::1
sudo systemctl restart postgresql redis-server
```

### Langkah 3.4: Salin dan Konfigurasi Layanan
```bash
# Nginx
sudo cp deployment/nginx/cloudflare-ips.conf /etc/nginx/snippets/cloudflare-ips.conf
sudo cp deployment/nginx/komikini.conf /etc/nginx/sites-available/komikini.conf
sudo ln -sf /etc/nginx/sites-available/komikini.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# PHP-FPM Pool
sudo cp deployment/php/komikini-fpm.conf /etc/php/8.3/fpm/pool.d/komikini.conf
sudo systemctl restart php8.3-fpm

# Supervisor
sudo cp deployment/supervisor/komikini-worker.conf /etc/supervisor/conf.d/komikini-worker.conf
sudo cp deployment/supervisor/komikini-scheduler.conf /etc/supervisor/conf.d/komikini-scheduler.conf
sudo supervisorctl reread && sudo supervisorctl update
```

---

## 4. Eksekusi Deployment Rutin
Untuk melakukan deployment rilis baru secara otomatis dan atomic:

```bash
cd /var/www/komikini/deployment/scripts
sudo -u www-data ./deploy.sh main
```

Skrip ini akan secara otomatis:
1. Mengkloning commit terbaru ke direktori rilis bertanda waktu.
2. Memasang symlink `.env` dan direktori shared `storage/`.
3. Menjalankan `composer install --no-dev --optimize-autoloader`.
4. Membangun aset frontend via Vite.
5. Menjalankan `php artisan migrate --force`.
6. Memanaskan cache aplikasi (`config`, `route`, `view`, `event`).
7. Mengalihkan symlink `/var/www/komikini/current` secara atomic.
8. Merestart worker queue dan mereload PHP-FPM.
9. Memverifikasi health check `/health/ready`.
10. Melakukan rollback otomatis apabila health check gagal.
