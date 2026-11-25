<?php

namespace Database\Seeders;

use App\Models\WhatsappLog;
use Illuminate\Database\Seeder;

class WhatsappLogSeeder extends Seeder
{
    public function run(): void
    {
        if (WhatsappLog::count() > 0) {
            return;
        }

        $samples = [
            [
                'sender_phone' => '6281234001101',
                'incoming_message' => 'CEK KEDIRI',
                'bot_reply' => "🟡 *Kualitas Udara Kota Kediri*\nAQI: 81 (Sedang) | PM2.5: 26.4 µg/m³\nAnalogi: 1 jam naik motor ≈ 0.14 batang rokok.\nRute Bersih: /rute-bersih",
                'command' => 'CEK',
                'hours_ago' => 7,
            ],
            [
                'sender_phone' => '6281234001102',
                'incoming_message' => 'JOGGING TULUNGAGUNG',
                'bot_reply' => "🏃 *Rekomendasi Jam Jogging — Kabupaten Tulungagung*\nWaktu Paling Bersih: 05:00 - 06:30 WIB (PM2.5 ~11.8 µg/m³).",
                'command' => 'JOGGING',
                'hours_ago' => 5,
            ],
            [
                'sender_phone' => '6281234001103',
                'incoming_message' => 'LAPOR SAMPAH Dekat Simpang Lima Gumul Kediri',
                'bot_reply' => "✅ *Laporan Titik Asap Diterima!*\nWilayah: Kabupaten Kediri • Kategori: Pembakaran Sampah Terbuka.\nTitik asap telah muncul di Peta Rute Bersih.",
                'command' => 'LAPOR',
                'hours_ago' => 4,
            ],
            [
                'sender_phone' => '6281234001104',
                'incoming_message' => 'CEK BLITAR',
                'bot_reply' => "🟡 *Kualitas Udara Kota Blitar*\nAQI: 66 (Sedang) | PM2.5: 19.4 µg/m³\nSaran: Gunakan masker medis saat melintasi jalan padat.",
                'command' => 'CEK',
                'hours_ago' => 3,
            ],
            [
                'sender_phone' => '6281234001105',
                'incoming_message' => 'CEK TRENGGALEK',
                'bot_reply' => "🟢 *Kualitas Udara Kabupaten Trenggalek*\nAQI: 44 (Baik) | PM2.5: 10.6 µg/m³\nUdara segar & aman untuk aktivitas luar ruangan.",
                'command' => 'CEK',
                'hours_ago' => 2,
            ],
            [
                'sender_phone' => '6281234001106',
                'incoming_message' => 'JOGGING KEDIRI',
                'bot_reply' => "🏃 *Rekomendasi Jam Jogging — Kota Kediri*\nWaktu Paling Bersih: 05:00 - 06:30 WIB. Hindari tepi jalan arteri saat jam berangkat kerja.",
                'command' => 'JOGGING',
                'hours_ago' => 1,
            ],
        ];

        foreach ($samples as $item) {
            WhatsappLog::create([
                'sender_phone' => $item['sender_phone'],
                'incoming_message' => $item['incoming_message'],
                'bot_reply' => $item['bot_reply'],
                'command' => $item['command'],
                'created_at' => now()->subHours($item['hours_ago']),
                'updated_at' => now()->subHours($item['hours_ago']),
            ]);
        }
    }
}
