<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmokeReport extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Pembakaran Sampah Terbuka',
        'Asap Kendaraan Pekat',
        'Debu Konstruksi',
    ];

    public const STATUSES = [
        'dilaporkan' => 'Dilaporkan',
        'diverifikasi' => 'Diverifikasi',
        'ditindaklanjuti' => 'Ditindaklanjuti',
    ];

    protected $fillable = [
        'region_id',
        'reporter_name',
        'reporter_phone',
        'category',
        'description',
        'latitude',
        'longitude',
        'photo_path',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'dilaporkan' => 'bg-red-100 text-red-700 border-red-200',
            'diverifikasi' => 'bg-amber-100 text-amber-800 border-amber-200',
            'ditindaklanjuti' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http')) {
            return $this->photo_path;
        }

        return asset('storage/' . ltrim($this->photo_path, '/'));
    }
}
