<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirQualityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'pm25',
        'pm10',
        'no2',
        'aqi',
        'category',
        'source',
        'recorded_at',
    ];

    protected $casts = [
        'pm25' => 'float',
        'pm10' => 'float',
        'no2' => 'float',
        'aqi' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public static function categorizeAqi(int $aqi): string
    {
        return match (true) {
            $aqi <= 50 => 'Baik',
            $aqi <= 100 => 'Sedang',
            $aqi <= 150 => 'Tidak Sehat bagi Kelompok Sensitif',
            default => 'Tidak Sehat',
        };
    }

    public static function metaByAqi(int $aqi): array
    {
        if ($aqi <= 50) {
            return [
                'category' => 'Baik',
                'short_label' => 'Baik',
                'color' => '#2E9E3F',
                'bg_class' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'badge_class' => 'bg-[#2E9E3F] text-white',
                'dot_class' => 'bg-[#2E9E3F]',
                'summary' => 'Udara segar & aman untuk seluruh aktivitas luar ruangan.',
            ];
        }

        if ($aqi <= 100) {
            return [
                'category' => 'Sedang',
                'short_label' => 'Sedang',
                'color' => '#EAB308',
                'bg_class' => 'bg-amber-50 text-amber-800 border-amber-200',
                'badge_class' => 'bg-amber-500 text-white',
                'dot_class' => 'bg-amber-500',
                'summary' => 'Kualitas udara dapat diterima; kelompok sensitif perlu waspada di jam sibuk.',
            ];
        }

        if ($aqi <= 150) {
            return [
                'category' => 'Tidak Sehat bagi Kelompok Sensitif',
                'short_label' => 'Sensitif',
                'color' => '#F97316',
                'bg_class' => 'bg-orange-50 text-orange-800 border-orange-200',
                'badge_class' => 'bg-orange-500 text-white',
                'dot_class' => 'bg-orange-500',
                'summary' => 'Anak, lansia, dan penderita asma sebaiknya membatasi aktivitas berat di luar.',
            ];
        }

        return [
            'category' => 'Tidak Sehat',
            'short_label' => 'Tidak Sehat',
            'color' => '#DC2626',
            'bg_class' => 'bg-red-50 text-red-800 border-red-200',
            'badge_class' => 'bg-red-600 text-white',
            'dot_class' => 'bg-red-600',
            'summary' => 'Gunakan masker N95/KF94 di luar ruangan dan hindari olahraga berat.',
        ];
    }

    public function getMetaAttribute(): array
    {
        return self::metaByAqi((int) $this->aqi);
    }
}
