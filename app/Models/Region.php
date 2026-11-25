<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function airQualityLogs(): HasMany
    {
        return $this->hasMany(AirQualityLog::class);
    }

    public function latestAirQuality(): HasOne
    {
        return $this->hasOne(AirQualityLog::class)->latestOfMany('recorded_at');
    }

    public function smokeReports(): HasMany
    {
        return $this->hasMany(SmokeReport::class);
    }

    public function activeSmokeReports(): HasMany
    {
        return $this->hasMany(SmokeReport::class)
            ->whereIn('status', ['dilaporkan', 'diverifikasi']);
    }
}
