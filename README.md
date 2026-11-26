---
title: NapasAman
emoji: 🌿
colorFrom: blue
colorTo: green
sdk: docker
app_port: 7860
pinned: false
---

# NapasAman — Cek Kualitas Udara. Hidup Lebih Sehat.

Aplikasi Progressive Web App (PWA) pemantauan kualitas udara berbasis data, **navigasi real-time rute rendah polusi (open-source)**, kalkulator paparan PM2.5, crowdsourcing lapor titik asap, dan WhatsApp Bot **"Sobat Napas"** (`+62 895-3668-66722`) khusus **Wilayah Eks-Karesidenan Kediri (7 Daerah)** untuk kompetisi **AIRIMPACT 2026 (Tim JosJiz AG)**.

## Cakupan Wilayah (Eks-Karesidenan Kediri)
1. Kota Kediri (`-7.8166, 112.0114`)
2. Kabupaten Kediri (`-7.8480, 112.0178`)
3. Kabupaten Tulungagung (`-8.0657, 111.9025`)
4. Kota Blitar (`-8.0983, 112.1681`)
5. Kabupaten Blitar (`-8.1286, 112.2483`)
6. Kabupaten Nganjuk (`-7.6051, 111.9035`)
7. Kabupaten Trenggalek (`-8.0500, 111.7167`)

---

## Panduan Instalasi (Fresh Clone)

Pastikan sudah terpasang **PHP >= 8.1**, **Composer**, **MySQL**, dan **Node.js >= 18**.

```powershell
# 1. Clone repository & masuk folder proyek
git clone https://github.com/muhammadshodiq20/napasaman.git
cd napasaman

# 2. Install dependensi Laravel
composer install

# 3. Salin file konfigurasi lingkungan (.env)
cp .env.example .env

# 4. Generate Application Key
php artisan key:generate
```

Buka file `.env` dan sesuaikan konfigurasi database MySQL, Gemini API, serta keamanan Webhook WhatsApp:
```env
DB_DATABASE=napasaman_db
DB_USERNAME=root
DB_PASSWORD=

GEMINI_API_KEY=isi_api_key_gemini_anda
GEMINI_MODEL=gemini-3.8-flash

WHATSAPP_BOT_NUMBER=62895366866722
WHATSAPP_WEBHOOK_SECRET=ganti_dengan_secret_acak_kuat
WHATSAPP_VERIFY_TOKEN=ganti_dengan_verify_token_meta
```

Lanjutkan persiapan database & storage publik:
```powershell
# 5. Buat database `napasaman_db` di MySQL, lalu jalankan migrasi & seeder
php artisan migrate --seed

# 6. Buat symbolic link agar foto laporan asap dapat diakses publik
php artisan storage:link

# 7. Install dependensi WhatsApp Bot (wa-bot)
cd wa-bot
npm install
cd ..
```

---

## Cara Menjalankan Aplikasi + WhatsApp Bot Asli (2 Terminal)

**Terminal 1 — Server Laravel (PWA, Navigasi & Webhook):**
```powershell
# Jika memakai Laragon di Windows dan PHP belum ada di PATH global:
$env:PATH = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64;$env:PATH"

php artisan serve
```

**Terminal 2 — WhatsApp Bot Asli (`wa-bot/` — Node.js + Baileys):**
```powershell
cd wa-bot
node index.js
```
1. Saat pertama kali dijalankan, **QR Code** akan muncul di terminal.
2. Buka WhatsApp di HP nomor bot (`+62 895-3668-66722`) → **Perangkat Tertaut (Linked Devices)** → **Tautkan Perangkat**, lalu scan QR Code.
3. Sesi login tersimpan di `wa-bot/auth_info/` (tidak perlu scan ulang) dan `wa-bot/index.js` otomatis mengirimkan header `X-Webhook-Secret` yang sesuai dengan `.env`.

Buka aplikasi di browser: **http://127.0.0.1:8000**

---

## Daftar Rute Utama
- `/` — Beranda & Dashboard Kualitas Udara 7 Wilayah Eks-Karesidenan Kediri
- `/rute-bersih` — **Navigasi Real-Time Rute Rendah Polusi** (Leaflet + OSM + OSRM Turn-by-Turn + GPS Live + Simulasi Jalan + Panduan Suara ID + Profil Pengguna)
- `/kalkulator-paparan` — Kalkulator & Visualisasi Paparan PM2.5
- `/lapor-asap` — Crowdsourcing Lapor Titik Asap Warga
- `/bot-wa` — Akses Langsung WhatsApp Bot **"Sobat Napas"** (`+62 895-3668-66722`) dengan pesan awal otomatis
- `/api/whatsapp/webhook` — REST API Webhook WhatsApp (`GET` verifikasi token & `POST` terproteksi `X-Webhook-Secret` / HMAC Meta Cloud API)
- `/login` & `/admin` — Panel Admin / Mitra RT-RW & Bank Sampah

## Pengujian Otomatis
```powershell
php artisan test --filter=NapasAmanTest
node --check wa-bot/index.js
```

## Akun Demo & Catatan Keamanan Produksi
Akun demo lokal dari seeder (`UserSeeder`):
- **Admin:** `admin@napasaman.id` / `napasaman2026`
- **Mitra RT/RW & Bank Sampah:** `mitra@napasaman.id` / `napasaman2026`
- **Warga:** `warga@napasaman.id` / `napasaman2026`

> **Penting untuk Rilis Publik / Produksi:**
> - Segera ganti atau nonaktifkan kredensial demo di atas sebelum deploy publik.
> - Gunakan nilai acak yang kuat untuk `WHATSAPP_WEBHOOK_SECRET` dan `WHATSAPP_VERIFY_TOKEN`.
> - Service `wa-bot/` (Baileys) sangat ideal untuk demo/kompetisi tanpa biaya; untuk skala produksi publik jangka panjang dengan traffic tinggi, sangat disarankan beralih ke **Meta WhatsApp Cloud API** resmi yang sudah didukung langsung oleh endpoint `/api/whatsapp/webhook`.
