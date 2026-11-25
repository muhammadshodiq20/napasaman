<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['name' => 'Kota Kediri', 'slug' => 'kota-kediri', 'latitude' => -7.8166, 'longitude' => 112.0114],
            ['name' => 'Kabupaten Kediri', 'slug' => 'kabupaten-kediri', 'latitude' => -7.8480, 'longitude' => 112.0178],
            ['name' => 'Kabupaten Tulungagung', 'slug' => 'kabupaten-tulungagung', 'latitude' => -8.0657, 'longitude' => 111.9025],
            ['name' => 'Kota Blitar', 'slug' => 'kota-blitar', 'latitude' => -8.0983, 'longitude' => 112.1681],
            ['name' => 'Kabupaten Blitar', 'slug' => 'kabupaten-blitar', 'latitude' => -8.1286, 'longitude' => 112.2483],
            ['name' => 'Kabupaten Nganjuk', 'slug' => 'kabupaten-nganjuk', 'latitude' => -7.6051, 'longitude' => 111.9035],
            ['name' => 'Kabupaten Trenggalek', 'slug' => 'kabupaten-trenggalek', 'latitude' => -8.0500, 'longitude' => 111.7167],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['slug' => $region['slug']],
                array_merge($region, ['is_active' => true])
            );
        }
    }
}
