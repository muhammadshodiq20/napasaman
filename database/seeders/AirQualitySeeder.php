<?php

namespace Database\Seeders;

use App\Models\AirQualityLog;
use App\Models\Region;
use Illuminate\Database\Seeder;

class AirQualitySeeder extends Seeder
{
    public function run(): void
    {
        if (AirQualityLog::count() > 0) {
            return;
        }

        // Baseline realistic PM2.5 per Kediri Raya region for demo/offline fallback
        $baselines = [
            'kota-kediri' => ['pm25' => 26.4, 'pm10' => 41.2, 'no2' => 18.6],
            'kabupaten-kediri' => ['pm25' => 21.8, 'pm10' => 34.5, 'no2' => 13.4],
            'kabupaten-tulungagung' => ['pm25' => 17.6, 'pm10' => 27.9, 'no2' => 11.2],
            'kota-blitar' => ['pm25' => 19.4, 'pm10' => 30.8, 'no2' => 12.8],
            'kabupaten-blitar' => ['pm25' => 14.2, 'pm10' => 22.6, 'no2' => 8.9],
            'kabupaten-nganjuk' => ['pm25' => 28.9, 'pm10' => 45.1, 'no2' => 16.5],
            'kabupaten-trenggalek' => ['pm25' => 10.6, 'pm10' => 17.4, 'no2' => 6.4],
        ];

        // Realistic 24-hour diurnal curve (lowest early morning 03-05, peak rush hours 07-09 & 17-19)
        $curve = [
            0 => 0.78, 1 => 0.74, 2 => 0.70, 3 => 0.66, 4 => 0.64, 5 => 0.70,
            6 => 0.86, 7 => 1.12, 8 => 1.24, 9 => 1.14, 10 => 1.00, 11 => 0.90,
            12 => 0.85, 13 => 0.82, 14 => 0.86, 15 => 0.95, 16 => 1.12, 17 => 1.28,
            18 => 1.24, 19 => 1.12, 20 => 1.00, 21 => 0.92, 22 => 0.86, 23 => 0.80,
        ];

        $regions = Region::all();
        $now = now()->startOfHour();

        foreach ($regions as $region) {
            $base = $baselines[$region->slug] ?? ['pm25' => 18.0, 'pm10' => 28.0, 'no2' => 10.0];

            for ($i = 23; $i >= 0; $i--) {
                $ts = $now->copy()->subHours($i);
                $factor = $curve[(int) $ts->format('G')] ?? 1.0;

                $pm25 = round($base['pm25'] * $factor, 2);
                $pm10 = round($base['pm10'] * $factor, 2);
                $no2 = round($base['no2'] * $factor, 2);
                $aqi = $this->estimateAqi($pm25);

                AirQualityLog::create([
                    'region_id' => $region->id,
                    'pm25' => $pm25,
                    'pm10' => $pm10,
                    'no2' => $no2,
                    'aqi' => $aqi,
                    'category' => AirQualityLog::categorizeAqi($aqi),
                    'source' => 'seeder-fallback',
                    'recorded_at' => $ts,
                ]);
            }
        }
    }

    private function estimateAqi(float $pm25): int
    {
        $breakpoints = [
            [0.0, 12.0, 0, 50],
            [12.1, 35.4, 51, 100],
            [35.5, 55.4, 101, 150],
            [55.5, 150.4, 151, 200],
        ];

        foreach ($breakpoints as [$cLow, $cHigh, $iLow, $iHigh]) {
            if ($pm25 <= $cHigh) {
                $ratio = ($pm25 - $cLow) / max(0.1, ($cHigh - $cLow));
                return (int) round($ratio * ($iHigh - $iLow) + $iLow);
            }
        }

        return 210;
    }
}
