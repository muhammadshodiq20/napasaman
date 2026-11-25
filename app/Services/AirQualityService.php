<?php

namespace App\Services;

use App\Models\AirQualityLog;
use App\Models\Region;
use App\Models\SmokeReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirQualityService
{
    public const KEDIRI_RAYA_BOUNDS = [
        'south' => -8.40,
        'north' => -7.35,
        'west' => 111.35,
        'east' => 112.55,
        'center_lat' => -7.90,
        'center_lng' => 111.98,
    ];

    /**
     * Get all active Kediri Raya regions with their latest air quality reading.
     * Refreshes from Open-Meteo when cached data is stale (> 30 min) or forced,
     * with automatic fallback to MySQL records when offline.
     */
    public function getRegionsWithLatestAirQuality(bool $forceRefresh = false, int $maxAgeMinutes = 30): Collection
    {
        $regions = Region::where('is_active', true)->orderBy('id')->get();

        if ($regions->isEmpty()) {
            return collect();
        }

        $cooldownKey = 'napasaman_openmeteo_sync_cooldown';
        $needsSync = $forceRefresh;

        if (! $needsSync && ! Cache::has($cooldownKey)) {
            $latestLive = AirQualityLog::where('source', 'open-meteo')
                ->latest('recorded_at')
                ->first();

            if (! $latestLive || $latestLive->recorded_at->lt(now()->subMinutes($maxAgeMinutes))) {
                $needsSync = true;
            }
        }

        if ($needsSync) {
            $synced = $this->syncAllRegionsFromOpenMeteo($regions);
            // Put a 5-minute cooldown if offline so page loads stay fast, or 30-min if synced
            Cache::put($cooldownKey, true, now()->addMinutes($synced ? 30 : 5));
        }

        $regions->load('latestAirQuality');

        return $regions->map(function (Region $region) {
            $log = $region->latestAirQuality;

            if (! $log) {
                $log = AirQualityLog::create([
                    'region_id' => $region->id,
                    'pm25' => 18.5,
                    'pm10' => 28.4,
                    'no2' => 11.2,
                    'aqi' => 64,
                    'category' => 'Sedang',
                    'source' => 'seeder-fallback',
                    'recorded_at' => now(),
                ]);
                $region->setRelation('latestAirQuality', $log);
            }

            return $region;
        });
    }

    /**
     * Batch-fetch current & hourly air quality for all 7 Kediri Raya regions
     * in a single Open-Meteo API request (fast, free, no API key).
     */
    public function syncAllRegionsFromOpenMeteo(Collection $regions): bool
    {
        try {
            $lats = $regions->pluck('latitude')->implode(',');
            $lngs = $regions->pluck('longitude')->implode(',');

            $response = Http::timeout(4)
                ->connectTimeout(2)
                ->get('https://air-quality-api.open-meteo.com/v1/air-quality', [
                    'latitude' => $lats,
                    'longitude' => $lngs,
                    'current' => 'pm10,pm2_5,nitrogen_dioxide,us_aqi',
                    'hourly' => 'pm2_5,pm10,nitrogen_dioxide,us_aqi',
                    'forecast_days' => 1,
                    'timezone' => 'Asia/Jakarta',
                ]);

            if (! $response->successful()) {
                return false;
            }

            $payload = $response->json();
            $items = is_array($payload) && array_is_list($payload) ? $payload : [$payload];

            foreach ($regions->values() as $idx => $region) {
                $item = $items[$idx] ?? null;
                if (! is_array($item) || empty($item['current'])) {
                    continue;
                }

                $current = $item['current'];
                $pm25 = round((float) ($current['pm2_5'] ?? 15.0), 2);
                $pm10 = round((float) ($current['pm10'] ?? 24.0), 2);
                $no2 = round((float) ($current['nitrogen_dioxide'] ?? 9.0), 2);
                $aqi = max(1, (int) round((float) ($current['us_aqi'] ?? $this->estimateAqiFromPm25($pm25))));
                $recordedAt = isset($current['time'])
                    ? Carbon::parse($current['time'], 'Asia/Jakarta')
                    : now();

                AirQualityLog::create([
                    'region_id' => $region->id,
                    'pm25' => $pm25,
                    'pm10' => $pm10,
                    'no2' => $no2,
                    'aqi' => $aqi,
                    'category' => AirQualityLog::categorizeAqi($aqi),
                    'source' => 'open-meteo',
                    'recorded_at' => $recordedAt,
                ]);

                if (! empty($item['hourly']['time'])) {
                    Cache::put(
                        "napasaman_hourly_{$region->id}",
                        $this->formatHourlyPayload($item['hourly']),
                        now()->addMinutes(45)
                    );
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Open-Meteo sync fallback triggered: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get 24-hour PM2.5 & AQI trend for a specific region (Open-Meteo cache or MySQL fallback).
     */
    public function getHourlyTrend(Region $region): array
    {
        $cached = Cache::get("napasaman_hourly_{$region->id}");
        if (is_array($cached) && ! empty($cached['labels'])) {
            return $cached;
        }

        // Build from recent MySQL logs or realistic Kediri Raya diurnal curve
        $logs = AirQualityLog::where('region_id', $region->id)
            ->orderByDesc('recorded_at')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();

        if ($logs->count() >= 12) {
            $labels = [];
            $pm25 = [];
            $aqi = [];
            foreach ($logs as $log) {
                $labels[] = $log->recorded_at->format('H:00');
                $pm25[] = round($log->pm25, 1);
                $aqi[] = (int) $log->aqi;
            }

            return [
                'labels' => $labels,
                'pm25' => $pm25,
                'aqi' => $aqi,
                'source' => $logs->last()->source ?? 'seeder-fallback',
            ];
        }

        $basePm25 = optional($region->latestAirQuality)->pm25 ?? 18.0;
        $multipliers = [
            0.78, 0.74, 0.70, 0.68, 0.66, 0.72,
            0.88, 1.12, 1.24, 1.15, 1.02, 0.92,
            0.86, 0.84, 0.88, 0.96, 1.14, 1.28,
            1.22, 1.10, 0.98, 0.90, 0.84, 0.80,
        ];

        $labels = [];
        $pm25 = [];
        $aqi = [];

        foreach ($multipliers as $hour => $factor) {
            $labels[] = sprintf('%02d:00', $hour);
            $val = round(max(4.5, $basePm25 * $factor), 1);
            $pm25[] = $val;
            $aqi[] = $this->estimateAqiFromPm25($val);
        }

        return [
            'labels' => $labels,
            'pm25' => $pm25,
            'aqi' => $aqi,
            'source' => 'seeder-fallback',
        ];
    }

    /**
     * Find the safest jogging time windows today from hourly PM2.5 trend.
     */
    public function getBestJoggingWindow(array $hourlyTrend): array
    {
        $labels = $hourlyTrend['labels'] ?? [];
        $pm25List = $hourlyTrend['pm25'] ?? [];

        // Consider sensible outdoor exercise hours: morning (05:00-09:00) & afternoon (15:00-18:00)
        $morningBest = ['hour' => '05:00 - 06:30 WIB', 'pm25' => 999.0, 'idx' => 5];
        $afternoonBest = ['hour' => '15:30 - 17:00 WIB', 'pm25' => 999.0, 'idx' => 15];

        foreach ($labels as $idx => $label) {
            $hourNum = (int) substr($label, 0, 2);
            $val = (float) ($pm25List[$idx] ?? 20.0);

            if ($hourNum >= 5 && $hourNum <= 8 && $val < $morningBest['pm25']) {
                $nextHour = sprintf('%02d:30', min($hourNum + 1, 9));
                $morningBest = [
                    'hour' => "{$label} - {$nextHour} WIB",
                    'pm25' => $val,
                    'idx' => $idx,
                ];
            }

            if ($hourNum >= 15 && $hourNum <= 17 && $val < $afternoonBest['pm25']) {
                $nextHour = sprintf('%02d:30', min($hourNum + 1, 18));
                $afternoonBest = [
                    'hour' => "{$label} - {$nextHour} WIB",
                    'pm25' => $val,
                    'idx' => $idx,
                ];
            }
        }

        if ($morningBest['pm25'] === 999.0) {
            $morningBest['pm25'] = 12.5;
        }
        if ($afternoonBest['pm25'] === 999.0) {
            $afternoonBest['pm25'] = 16.5;
        }

        $primary = $morningBest['pm25'] <= $afternoonBest['pm25'] ? $morningBest : $afternoonBest;

        return [
            'primary' => $primary,
            'morning' => $morningBest,
            'afternoon' => $afternoonBest,
        ];
    }

    /**
     * Activity recommendations tailored to the 3 user segments.
     */
    public function getSegmentRecommendations(int $aqi, float $pm25, array $bestJoggingWindow): array
    {
        $jogHour = $bestJoggingWindow['primary']['hour'] ?? '05:30 - 06:30 WIB';
        $jogPm25 = $bestJoggingWindow['primary']['pm25'] ?? $pm25;

        if ($aqi <= 50) {
            return [
                'komuter' => [
                    'title' => 'Komuter & Mahasiswa',
                    'status' => 'Aman Beraktivitas',
                    'badge' => 'bg-[#2E9E3F] text-white',
                    'headline' => 'Perjalanan motor & sepeda sangat nyaman hari ini.',
                    'tips' => [
                        'Udara relatif bersih di koridor utama Kediri Raya; masker kain/medis opsional di jalan padat.',
                        'Tetap pilih jalur rindang melalui fitur Rute Bersih untuk menghindari titik pembakaran lokal.',
                        'Cukup minum air putih saat bepergian ke kampus atau tempat kerja.',
                    ],
                ],
                'keluarga' => [
                    'title' => 'Keluarga (Anak & Lansia)',
                    'status' => 'Aman Bermain di Luar',
                    'badge' => 'bg-[#2E9E3F] text-white',
                    'headline' => 'Waktu ideal untuk mengajak anak dan lansia berjemur pagi.',
                    'tips' => [
                        'Buka jendela rumah pada pagi hari agar sirkulasi udara bersih mengalir optimal.',
                        'Anak-anak dan lansia aman berjalan santai di taman kota atau alun-alun.',
                        'Hindari membakar sampah daun di pekarangan agar kualitas udara lingkungan tetap terjaga.',
                    ],
                ],
                'pelari' => [
                    'title' => 'Pelari / Jogging',
                    'status' => 'Optimal untuk Olahraga',
                    'badge' => 'bg-[#2E9E3F] text-white',
                    'headline' => "Jam olahraga paling aman hari ini: {$jogHour} (PM2.5 ~{$jogPm25} µg/m³).",
                    'tips' => [
                        'Latihan tempo, easy run, hingga long run aman dilakukan di luar ruangan.',
                        'Rekomendasi area: Taman Brantas, SLG pagi hari, atau jalur pedesaan hijau.',
                        'Pantau peta titik asap sebelum memulai lari sore.',
                    ],
                ],
            ];
        }

        if ($aqi <= 100) {
            return [
                'komuter' => [
                    'title' => 'Komuter & Mahasiswa',
                    'status' => 'Gunakan Masker di Jalan Padat',
                    'badge' => 'bg-amber-500 text-white',
                    'headline' => 'Waspadai lonjakan asap kendaraan pada jam berangkat & pulang kerja.',
                    'tips' => [
                        'Gunakan masker medis 3-ply atau KF94 saat mengendarai motor di jalan arteri.',
                        'Gunakan fitur Rute Bersih untuk menghindari titik macet dan laporan asap aktif.',
                        'Hindari menunggu terlalu lama di belakang knalpot truk atau bus di persimpangan.',
                    ],
                ],
                'keluarga' => [
                    'title' => 'Keluarga (Anak & Lansia)',
                    'status' => 'Pantau Kelompok Sensitif',
                    'badge' => 'bg-amber-500 text-white',
                    'headline' => 'Aktivitas luar ruangan masih aman, batasi di dekat jalan raya padat.',
                    'tips' => [
                        'Anak dengan riwayat asma dan lansia disarankan bermain di ruang terbuka hijau pada pagi hari.',
                        'Tutup jendela rumah yang menghadap langsung ke jalan raya pada pukul 07.00–09.00 & 16.00–18.00.',
                        'Siapkan obat pelega napas (inhaler) bagi anggota keluarga yang sensitif.',
                    ],
                ],
                'pelari' => [
                    'title' => 'Pelari / Jogging',
                    'status' => 'Pilih Jam Terbersih',
                    'badge' => 'bg-amber-500 text-white',
                    'headline' => "Jam jogging terbaik hari ini: {$jogHour} (estimasi PM2.5 {$jogPm25} µg/m³).",
                    'tips' => [
                        'Prioritaskan lari di pagi buta sebelum lalu lintas padat dan hindari tepi jalan utama.',
                        'Jika berolahraga sore, turunkan intensitas atau pilih area taman/stadion yang rimbun.',
                        'Gunakan pernapasan hidung saat pace santai untuk membantu penyaringan partikel.',
                    ],
                ],
            ];
        }

        return [
            'komuter' => [
                'title' => 'Komuter & Mahasiswa',
                'status' => 'Wajib Masker KF94 / N95',
                'badge' => 'bg-orange-600 text-white',
                'headline' => 'Paparan PM2.5 tinggi di jalan raya; perlindungan pernapasan sangat disarankan.',
                'tips' => [
                    'Wajib pakai masker KF94/N95 yang rapat saat naik motor atau berjalan di tepi jalan.',
                    'Aktifkan fitur Rute Bersih untuk memutar dari hotspot asap & jalan berpolusi tinggi.',
                    'Cuci wajah dan bilas hidung dengan larutan saline setelah berkendara jauh.',
                ],
            ],
            'keluarga' => [
                'title' => 'Keluarga (Anak & Lansia)',
                'status' => 'Batasi Aktivitas Luar',
                'badge' => 'bg-red-600 text-white',
                'headline' => 'Anak, ibu hamil, dan lansia disarankan lebih banyak berada di dalam ruangan.',
                'tips' => [
                    'Tutup rapat pintu dan jendela, nyalakan air purifier atau kipas dengan filter jika tersedia.',
                    'Tunda aktivitas bermain di luar ruangan hingga indeks AQI turun di bawah 100.',
                    'Segera periksa ke puskesmas/dokter jika muncul batuk menetap atau sesak napas.',
                ],
            ],
            'pelari' => [
                'title' => 'Pelari / Jogging',
                'status' => 'Alihkan ke Indoor / Ringan',
                'badge' => 'bg-red-600 text-white',
                'headline' => "Udara kurang ideal untuk lari berat. Jendela terbersih relatif: {$jogHour}.",
                'tips' => [
                    'Ganti latihan lari intensitas tinggi dengan workout indoor (treadmill, strength, yoga).',
                    'Saat jogging, laju napas meningkat 4–5x sehingga PM2.5 masuk jauh lebih dalam ke paru.',
                    'Jika tetap ke luar, batasi durasi < 20 menit dengan jalan santai dan pantau gejala napas.',
                ],
            ],
        ];
    }

    /**
     * Calculate PM2.5 inhalation exposure and cigarette analogy.
     */
    public function calculateExposure(float $ambientPm25, string $activity, int $durationMinutes): array
    {
        $profiles = [
            'motor' => [
                'label' => 'Naik Motor di Jalan Raya',
                'multiplier' => 1.65,
                'ventilation_m3_per_hour' => 0.85,
                'tips' => [
                    'Gunakan masker KF94/N95 (bukan masker kain tipis) dan tutup kaca helm rapat.',
                    'Hindari membuntuti langsung knalpot truk, bus, atau kendaraan berasap pekat.',
                    'Gunakan fitur Rute Bersih NapasAman untuk memangkas paparan hingga 20–35%.',
                ],
            ],
            'jalan_kaki' => [
                'label' => 'Jalan Kaki di Luar Ruangan',
                'multiplier' => 1.15,
                'ventilation_m3_per_hour' => 1.35,
                'tips' => [
                    'Berjalan di sisi trotoar yang lebih jauh dari aspal atau jalur rindang berpohon.',
                    'Gunakan masker medis/KF94 ketika melintasi persimpangan padat atau area konstruksi.',
                    'Hindari berjalan kaki di dekat lokasi pembakaran sampah terbuka.',
                ],
            ],
            'jogging' => [
                'label' => 'Jogging / Lari Luar Ruangan',
                'multiplier' => 1.10,
                'ventilation_m3_per_hour' => 2.80,
                'tips' => [
                    'Pilih jam lari paling bersih (biasanya pukul 05.00–06.30 WIB) sebelum jam sibuk.',
                    'Lari di taman kota, stadion, atau jalan desa yang jauh dari arus kendaraan bermotor.',
                    'Jika AQI > 100, kurangi durasi/intensitas atau pindahkan sesi latihan ke dalam ruangan.',
                ],
            ],
            'dalam_rumah' => [
                'label' => 'Aktivitas di Dalam Rumah',
                'multiplier' => 0.55,
                'ventilation_m3_per_hour' => 0.50,
                'tips' => [
                    'Tutup jendela saat ada asap pembakaran sampah di sekitar lingkungan rumah.',
                    'Hindari asap rokok dalam ruangan dan pastikan ventilasi dapur baik saat memasak.',
                    'Tanaman hias penyaring udara & pembersihan debu basah membantu menjaga udara indoor.',
                ],
            ],
        ];

        $profile = $profiles[$activity] ?? $profiles['motor'];
        $hours = max(1, $durationMinutes) / 60.0;

        $effectivePm25 = round($ambientPm25 * $profile['multiplier'], 1);
        $inhaledAirM3 = round($profile['ventilation_m3_per_hour'] * $hours, 2);
        $totalInhaledUg = round($effectivePm25 * $inhaledAirM3, 1);

        // Berkeley Earth equivalence: 22 ug/m3 for 24h (~12 m3 resting air = 264 ug PM2.5) ~= 1 cigarette
        $cigarettes = round($totalInhaledUg / 264.0, 2);
        // Scale to 24h daily ambient equivalent for clearer contextual reading
        $dailyCigarettesIfRepeated = round(($effectivePm25 / 22.0), 2);

        $riskLevel = match (true) {
            $totalInhaledUg < 25 => ['label' => 'Rendah', 'color' => 'emerald', 'desc' => 'Paparan masih tergolong ringan bagi paru-paru sehat.'],
            $totalInhaledUg < 75 => ['label' => 'Sedang', 'color' => 'amber', 'desc' => 'Paparan mulai signifikan bila dilakukan rutin setiap hari.'],
            $totalInhaledUg < 160 => ['label' => 'Tinggi', 'color' => 'orange', 'desc' => 'Disarankan memakai masker KF94/N95 atau mengurangi durasi paparan.'],
            default => ['label' => 'Sangat Tinggi', 'color' => 'red', 'desc' => 'Berisiko memicu iritasi saluran napas; segera gunakan proteksi maksimal.'],
        };

        return [
            'activity_key' => $activity,
            'activity_label' => $profile['label'],
            'duration_minutes' => $durationMinutes,
            'ambient_pm25' => round($ambientPm25, 1),
            'effective_pm25' => $effectivePm25,
            'inhaled_air_m3' => $inhaledAirM3,
            'total_inhaled_ug' => $totalInhaledUg,
            'cigarettes' => $cigarettes,
            'monthly_cigarettes' => round($cigarettes * 30, 1),
            'daily_ambient_cigarettes' => $dailyCigarettesIfRepeated,
            'risk' => $riskLevel,
            'tips' => $profile['tips'],
        ];
    }

    /**
     * Popular location presets strictly inside Wilayah Eks-Karesidenan Kediri.
     */
    public function getKediriRayaPresets(): array
    {
        return [
            ['name' => 'Simpang Lima Gumul (SLG)', 'region' => 'Kabupaten Kediri', 'lat' => -7.8124, 'lng' => 112.0626],
            ['name' => 'Alun-Alun Kota Kediri', 'region' => 'Kota Kediri', 'lat' => -7.8226, 'lng' => 112.0117],
            ['name' => 'Stasiun Kediri', 'region' => 'Kota Kediri', 'lat' => -7.8174, 'lng' => 112.0155],
            ['name' => 'Kampus UNP Kediri', 'region' => 'Kota Kediri', 'lat' => -7.8012, 'lng' => 111.9998],
            ['name' => 'Taman Brantas Kediri', 'region' => 'Kota Kediri', 'lat' => -7.8118, 'lng' => 112.0084],
            ['name' => 'Kampung Inggris Pare', 'region' => 'Kabupaten Kediri', 'lat' => -7.7676, 'lng' => 112.1924],
            ['name' => 'Alun-Alun Tulungagung', 'region' => 'Kabupaten Tulungagung', 'lat' => -8.0655, 'lng' => 111.9023],
            ['name' => 'Stasiun Tulungagung', 'region' => 'Kabupaten Tulungagung', 'lat' => -8.0638, 'lng' => 111.9044],
            ['name' => 'Alun-Alun Kota Blitar', 'region' => 'Kota Blitar', 'lat' => -8.0983, 'lng' => 112.1681],
            ['name' => 'Makam Bung Karno Blitar', 'region' => 'Kota Blitar', 'lat' => -8.0846, 'lng' => 112.1764],
            ['name' => 'Kanigoro (Pusat Kab. Blitar)', 'region' => 'Kabupaten Blitar', 'lat' => -8.1286, 'lng' => 112.2165],
            ['name' => 'Alun-Alun Nganjuk', 'region' => 'Kabupaten Nganjuk', 'lat' => -7.6042, 'lng' => 111.9012],
            ['name' => 'Alun-Alun Trenggalek', 'region' => 'Kabupaten Trenggalek', 'lat' => -8.0504, 'lng' => 111.7073],
        ];
    }

    public function estimateAqiFromPm25(float $pm25): int
    {
        $breakpoints = [
            [0.0, 12.0, 0, 50],
            [12.1, 35.4, 51, 100],
            [35.5, 55.4, 101, 150],
            [55.5, 150.4, 151, 200],
            [150.5, 250.4, 201, 300],
        ];

        foreach ($breakpoints as [$cLow, $cHigh, $iLow, $iHigh]) {
            if ($pm25 <= $cHigh) {
                $ratio = ($pm25 - $cLow) / max(0.1, ($cHigh - $cLow));
                return (int) round($ratio * ($iHigh - $iLow) + $iLow);
            }
        }

        return 301;
    }

    private function formatHourlyPayload(array $hourly): array
    {
        $times = $hourly['time'] ?? [];
        $pm25 = $hourly['pm2_5'] ?? [];
        $aqi = $hourly['us_aqi'] ?? [];

        $labels = [];
        $pm25Clean = [];
        $aqiClean = [];

        foreach ($times as $idx => $time) {
            $labels[] = Carbon::parse($time, 'Asia/Jakarta')->format('H:00');
            $p = round((float) ($pm25[$idx] ?? 15.0), 1);
            $pm25Clean[] = $p;
            $aqiClean[] = (int) round((float) ($aqi[$idx] ?? $this->estimateAqiFromPm25($p)));
        }

        return [
            'labels' => $labels,
            'pm25' => $pm25Clean,
            'aqi' => $aqiClean,
            'source' => 'open-meteo',
        ];
    }

    public function isWithinKediriRaya(float $lat, float $lng): bool
    {
        $b = self::KEDIRI_RAYA_BOUNDS;

        return $lat >= $b['south'] && $lat <= $b['north']
            && $lng >= $b['west'] && $lng <= $b['east'];
    }

    public function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * (sin($dLng / 2) ** 2);

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));
    }

    /**
     * Find the nearest region among the 7 Eks-Karesidenan Kediri regions.
     */
    public function findNearestRegion(float $lat, float $lng, ?Collection $regions = null): array
    {
        $regions = $regions ?? $this->getRegionsWithLatestAirQuality(false, 60);

        $nearest = $regions->first();
        $minDistance = PHP_FLOAT_MAX;

        foreach ($regions as $region) {
            $dist = $this->haversineKm($lat, $lng, (float) $region->latitude, (float) $region->longitude);
            if ($dist < $minDistance) {
                $minDistance = $dist;
                $nearest = $region;
            }
        }

        return [
            'region' => $nearest,
            'distance_km' => round($minDistance, 1),
            'within_kediri_raya' => $this->isWithinKediriRaya($lat, $lng),
        ];
    }

    /**
     * Find active smoke reports sorted by proximity to a coordinate (within radiusKm).
     */
    public function getNearbyActiveSmokeReports(float $lat, float $lng, float $radiusKm = 20.0, int $limit = 5): Collection
    {
        $reports = SmokeReport::with('region')
            ->whereIn('status', ['dilaporkan', 'diverifikasi'])
            ->latest()
            ->get();

        return $reports->map(function (SmokeReport $report) use ($lat, $lng) {
            $report->setAttribute(
                'distance_km',
                round($this->haversineKm($lat, $lng, (float) $report->latitude, (float) $report->longitude), 1)
            );
            return $report;
        })
            ->filter(fn (SmokeReport $r) => $r->distance_km <= $radiusKm)
            ->sortBy('distance_km')
            ->take($limit)
            ->values();
    }
}
