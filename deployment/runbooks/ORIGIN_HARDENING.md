# Komikini Origin & Edge Hardening Runbook

Panduan pengamanan server origin (VPS), konfigurasi Cloudflare proxy, firewall UFW, pembatasan akses SSH, dan isolasi jaringan layanan backend.

---

## 1. Arsitektur Pertahanan Berlapis (Defense-in-Depth)

```text
Visitor / Bot
     │
     ▼
Cloudflare Edge
  ├─ DNS Proxied (Orange Cloud) - IP Asli Origin Disembunyikan
  ├─ Cloudflare WAF Managed Rules & Bot Management
  ├─ Turnstile Captcha Adaptif
  ├─ Rate Limiting Edge
  └─ Authenticated Origin Pulls (AOP) Client Certificate
     │
     ▼
Origin VPS Firewall (UFW)
  ├─ Port 80 / 443: Hanya Menerima Traffic dari IP Resmi Cloudflare
  ├─ Port 22 (SSH): Hanya dari IP VPN / Operator Allowlist
  └─ Port 5432 (Postgres) & 6379 (Redis): Bind 127.0.0.1 (Tidak dapat diakses publik)
     │
     ▼
Nginx & PHP-FPM
  ├─ Real IP Restoration via CF-Connecting-IP
  ├─ Security Headers Hardening
  └─ Minimum File Permissions (Non-root www-data)
```

---

## 2. Konfigurasi Firewall UFW di Server Origin

Jalankan perintah berikut di VPS Ubuntu 24.04 untuk memblokir seluruh traffic langsung ke port 80/443 kecuali yang berasal dari IP Cloudflare:

```bash
# Reset default policy
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH dari IP terpercaya operator saja (ganti dengan IP statis/VPN Anda)
sudo ufw allow from 203.0.113.50 to any port 22 proto tcp comment 'Operator SSH'

# Izinkan Traffic HTTP/HTTPS HANYA dari blok IP resmi Cloudflare
# (Gunakan skrip loop berikut)
for ip in $(curl -s https://www.cloudflare.com/ips-v4); do
    sudo ufw allow proto tcp from $ip to any port 80,443 comment 'Cloudflare IPv4'
done

for ip in $(curl -s https://www.cloudflare.com/ips-v6); do
    sudo ufw allow proto tcp from $ip to any port 80,443 comment 'Cloudflare IPv6'
done

# Aktifkan UFW
sudo ufw enable
sudo ufw status verbose
```

---

## 3. Cloudflare Authenticated Origin Pulls (AOP)

Untuk memastikan bahwa origin Nginx hanya melayani request yang benar-benar diteruskan oleh Cloudflare (mencegah penyerang yang mengetahui IP VPS origin langsung melewati edge):

1. Unduh sertifikat origin pull resmi Cloudflare:
   ```bash
   sudo mkdir -p /etc/nginx/certs
   sudo curl -s https://developers.cloudflare.com/ssl/static/authenticated_origin_pull_ca.pem \
       -o /etc/nginx/certs/cloudflare_origin_pull_ca.pem
   ```
2. Tambahkan direktif berikut ke dalam blok `server` SSL di Nginx:
   ```nginx
   ssl_client_certificate /etc/nginx/certs/cloudflare_origin_pull_ca.pem;
   ssl_verify_client on;
   ```
3. Aktifkan **Authenticated Origin Pulls** di Dashboard Cloudflare: `SSL/TLS` -> `Origin Server` -> `Authenticated Origin Pulls: ON`.

---

## 4. Pengerasan SSH & Akun Sistem

Sunting `/etc/ssh/sshd_config.d/hardening.conf`:
```text
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
ClientAliveInterval 300
ClientAliveCountMax 2
```
Terapkan:
```bash
sudo sshd -t && sudo systemctl restart ssh
```

---

## 5. Isolasi Layanan Database & Redis

Pastikan PostgreSQL dan Redis tidak membuka port ke interface publik (`0.0.0.0`):

1. **PostgreSQL (`/etc/postgresql/16/main/postgresql.conf`):**
   ```text
   listen_addresses = 'localhost'
   ```
2. **Redis (`/etc/redis/redis.conf`):**
   ```text
   bind 127.0.0.1 ::1
   protected-mode yes
   ```
Verifikasi dengan `ss -tulpn`: pastikan kolom `Local Address` hanya menampilkan `127.0.0.1:5432` dan `127.0.0.1:6379`.

---

## 6. Hak Akses Berkas (File Permissions)
```bash
# Kepemilikan kode
sudo chown -R www-data:www-data /var/www/komikini

# Direktori 755, berkas 644
sudo find /var/www/komikini/current -type d -exec chmod 755 {} \;
sudo find /var/www/komikini/current -type f -exec chmod 644 {} \;

# Berkas sensitif hanya dapat dibaca pemilik (www-data)
sudo chmod 600 /var/www/komikini/shared/.env
```
