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

Aplikasi Progressive Web App (PWA) pemantauan kualitas udara berbasis data, navigasi rute rendah polusi, kalkulator paparan PM2.5, crowdsourcing lapor titik asap, dan WhatsApp Bot **"Sobat Napas"** khusus **Wilayah Eks-Karesidenan Kediri (7 Daerah)** untuk kompetisi **AIRIMPACT 2026 (Tim JosJiz AG)**.

## Cakupan Wilayah (Eks-Karesidenan Kediri)
1. Kota Kediri (`-7.8166, 112.0114`)
2. Kabupaten Kediri (`-7.8480, 112.0178`)
3. Kabupaten Tulungagung (`-8.0657, 111.9025`)
4. Kota Blitar (`-8.0983, 112.1681`)
5. Kabupaten Blitar (`-8.1286, 112.2483`)
6. Kabupaten Nganjuk (`-7.6051, 111.9035`)
7. Kabupaten Trenggalek (`-8.0500, 111.7167`)

## Cara Menjalankan Aplikasi + WhatsApp Bot Asli (Baileys + Gemini AI)

Buka **2 terminal terpisah** di folder proyek:

**Terminal 1 — Server Laravel (Backend, PWA & Webhook):**
```powershell
$env:PATH = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64;$env:PATH"
php artisan serve
```

**Terminal 2 — WhatsApp Bot Asli (`wa-bot/` — Node.js + Baileys):**
```powershell
cd wa-bot
npm install
node index.js
```
1. Saat pertama kali dijalankan, **QR Code** akan muncul di terminal.
2. Buka WhatsApp di HP → **Perangkat Tertaut (Linked Devices)** → **Tautkan Perangkat**, lalu scan QR Code.
3. Sesi login tersimpan otomatis di `wa-bot/auth_info/` sehingga tidak perlu scan ulang saat restart.
4. Atur `GEMINI_API_KEY` dan `GEMINI_MODEL` di `.env` (default `gemini-2.5-flash` dengan otomatis fallback ke `gemini-3.8-flash` / rule-based jika kuota limit).

Buka browser ke: **http://127.0.0.1:8000**

## Daftar Rute Utama
- `/` — Beranda & Dashboard Kualitas Udara 7 Wilayah Eks-Karesidenan Kediri
- `/rute-bersih` — Peta Navigasi Rute Rendah Polusi (Leaflet + OSM + OSRM)
- `/kalkulator-paparan` — Kalkulator & Visualisasi Paparan PM2.5
- `/lapor-asap` — Crowdsourcing Lapor Titik Asap Warga
- `/bot-wa` — Simulasi WhatsApp Bot **"Sobat Napas"**
- `/api/whatsapp/webhook` — REST API Webhook WhatsApp (`POST`, siap Fonnte & Meta Cloud API)
- `/login` & `/admin` — Panel Admin / Mitra RT-RW & Bank Sampah

## Akun Demo Panel Admin / Mitra
- **Admin:** `admin@napasaman.id` / `napasaman2026`
- **Mitra RT/RW & Bank Sampah:** `mitra@napasaman.id` / `napasaman2026`
- **Warga:** `warga@napasaman.id` / `napasaman2026`

## Contoh Webhook WhatsApp (`POST /api/whatsapp/webhook`)
```json
{
  "sender": "6281234567890",
  "message": "CEK KEDIRI"
}
```
Perintah yang didukung:
- `CEK [Nama Wilayah]` (contoh: `CEK KEDIRI`, `CEK TULUNGAGUNG`)
- `JOGGING [Nama Wilayah]` (contoh: `JOGGING BLITAR`)
- `LAPOR [Kategori] [Lokasi]` (contoh: `LAPOR SAMPAH Dekat Simpang Lima Gumul Kediri`)
