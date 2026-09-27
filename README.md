# Komikini

Platform baca Manga, Manhwa, dan Manhua Bahasa Indonesia yang cepat, modern, dan responsif.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Inertia.js v2, React 19, TypeScript
- **Styling:** Tailwind CSS v4 (Editorial Brutalist Theme)
- **Database:** SQLite (Development) / PostgreSQL (Production)
- **Cache:** Redis / Cache with Stale-While-Revalidate Fallback

## Fitur Utama

- **Discovery Catalog:**
  - Update Rilis Terbaru dengan paginasi
  - Komik Populer & Rekomendasi Pilihan
  - Filter format cepat (Manga, Manhwa, Manhua)
  - Direktori Genre lengkap dengan pencarian real-time
- **Detail Komik:**
  - Informasi komik, status publikasi, pengarang, dan daftar genre
  - Daftar chapter dengan pencarian judul/nomor dan sorting (terbaru / terlama)
  - Upsert metadata otomatis ke database lokal
- **Performance & Reliability:**
  - Stale-while-revalidate caching layer untuk upstream provider
  - Request Correlation ID tracking di response header dan antarmuka
  - Rate limiting pencarian
  - Desain editorial brutalist tanpa overflow di semua ukuran layar (Mobile, Tablet, Desktop)

## Panduan Instalasi Lokal

### 1. Prasyarat
- PHP >= 8.2 (dengan ekstensi `pdo_sqlite`, `curl`, `mbstring`)
- Composer >= 2.0
- Node.js >= 20 & npm

### 2. Langkah Setup

```bash
# Clone repository
git clone https://github.com/DarvinExa/komikini.git
cd komikini

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database & migrations
php artisan migrate

# Build frontend assets
npm run build

# Jalankan development server
php artisan serve
```

## Menjalankan Pengujian

```bash
# Unit & Feature Tests
php artisan test

# Linting & Formatting
php vendor/bin/pint --test
npm run lint
npm run typecheck
```

## Lisensi

Proyek ini dilisensikan di bawah lisensi MIT.
