<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\SmokeReport;
use Illuminate\Database\Seeder;

class SmokeReportSeeder extends Seeder
{
    public function run(): void
    {
        if (SmokeReport::count() > 0) {
            return;
        }

        $regions = Region::pluck('id', 'slug');

        $reports = [
            [
                'slug' => 'kota-kediri',
                'reporter_name' => 'Dimas Arya',
                'reporter_phone' => '081234510001',
                'category' => 'Pembakaran Sampah Terbuka',
                'description' => 'Pembakaran sampah plastik dan daun kering di lahan kosong pinggir Jl. Ahmad Yani, asap menyebar ke jalan dan permukiman RT 03.',
                'latitude' => -7.8204,
                'longitude' => 112.0258,
                'status' => 'diverifikasi',
                'admin_notes' => 'Pengurus RT 03 sudah menuju lokasi untuk edukasi penghentian pembakaran dan koordinasi angkut ke Bank Sampah.',
            ],
            [
                'slug' => 'kota-kediri',
                'reporter_name' => 'Nadia Rahmawati',
                'reporter_phone' => '081234510002',
                'category' => 'Asap Kendaraan Pekat',
                'description' => 'Antrean truk dan bus padat di simpang Jembatan Semampir menyebabkan asap knalpot pekat dan bau solar menyengat saat jam pulang kerja.',
                'latitude' => -7.8045,
                'longitude' => 112.0089,
                'status' => 'dilaporkan',
                'admin_notes' => null,
            ],
            [
                'slug' => 'kabupaten-kediri',
                'reporter_name' => 'Hendra Wijaya',
                'reporter_phone' => '081234510003',
                'category' => 'Pembakaran Sampah Terbuka',
                'description' => 'Titik pembakaran jerami dan sampah rumah tangga dekat jalan lingkar arah Simpang Lima Gumul (SLG) sisi barat.',
                'latitude' => -7.8168,
                'longitude' => 112.0532,
                'status' => 'dilaporkan',
                'admin_notes' => null,
            ],
            [
                'slug' => 'kabupaten-tulungagung',
                'reporter_name' => 'Siti Aminah',
                'reporter_phone' => '081234510004',
                'category' => 'Pembakaran Sampah Terbuka',
                'description' => 'Pembakaran sampah sore hari di sekitar tepi sungai dekat Kedungwaru, asap masuk ke rumah warga dan mengganggu anak-anak.',
                'latitude' => -8.0548,
                'longitude' => 111.9087,
                'status' => 'diverifikasi',
                'admin_notes' => 'Mitra Bank Sampah Kedungwaru telah menghubungi ketua RW setempat.',
            ],
            [
                'slug' => 'kota-blitar',
                'reporter_name' => 'Bagus Prasetyo',
                'reporter_phone' => '081234510005',
                'category' => 'Debu Konstruksi',
                'description' => 'Pengerjaan perbaikan drainase dan galian jalan di sekitar Jl. Merdeka Timur menimbulkan debu tebal saat truk melintas.',
                'latitude' => -8.0972,
                'longitude' => 112.1745,
                'status' => 'dilaporkan',
                'admin_notes' => null,
            ],
            [
                'slug' => 'kabupaten-blitar',
                'reporter_name' => 'Rina Kusuma',
                'reporter_phone' => '081234510006',
                'category' => 'Pembakaran Sampah Terbuka',
                'description' => 'Pembakaran ranting dan sampah pasar di belakang area pertokoan Kanigoro sudah berhasil dipadamkan setelah warga diarahkan ke TPS3R.',
                'latitude' => -8.1251,
                'longitude' => 112.2214,
                'status' => 'ditindaklanjuti',
                'admin_notes' => 'Selesai ditindaklanjuti bersama Karang Taruna & pengelola TPS3R Kanigoro.',
            ],
            [
                'slug' => 'kabupaten-nganjuk',
                'reporter_name' => 'Fajar Nugroho',
                'reporter_phone' => '081234510007',
                'category' => 'Asap Kendaraan Pekat',
                'description' => 'Kepadatan kendaraan berat di jalur arteri Nganjuk menuju Kertosono memicu kabut asap kendaraan pada pagi hari.',
                'latitude' => -7.6031,
                'longitude' => 111.9284,
                'status' => 'dilaporkan',
                'admin_notes' => null,
            ],
        ];

        foreach ($reports as $index => $item) {
            $regionId = $regions[$item['slug']] ?? null;
            if (! $regionId) {
                continue;
            }

            SmokeReport::create([
                'region_id' => $regionId,
                'reporter_name' => $item['reporter_name'],
                'reporter_phone' => $item['reporter_phone'],
                'category' => $item['category'],
                'description' => $item['description'],
                'latitude' => $item['latitude'],
                'longitude' => $item['longitude'],
                'status' => $item['status'],
                'admin_notes' => $item['admin_notes'],
                'created_at' => now()->subHours(18 - ($index * 2)),
                'updated_at' => now()->subHours(max(1, 16 - ($index * 2))),
            ]);
        }
    }
}
