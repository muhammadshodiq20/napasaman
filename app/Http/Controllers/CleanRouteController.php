<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\SmokeReport;
use App\Services\AirQualityService;
use Illuminate\View\View;

class CleanRouteController extends Controller
{
    public function __construct(
        protected AirQualityService $airQualityService
    ) {}

    public function index(): View
    {
        $regions = $this->airQualityService->getRegionsWithLatestAirQuality();

        $activeReports = SmokeReport::with('region')
            ->whereIn('status', ['dilaporkan', 'diverifikasi'])
            ->latest()
            ->get();

        $stationsPayload = $regions->map(function (Region $r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'pm25' => round((float) ($r->latestAirQuality->pm25 ?? 18.0), 1),
                'aqi' => (int) ($r->latestAirQuality->aqi ?? 60),
                'category' => $r->latestAirQuality->category ?? 'Sedang',
                'color' => $r->latestAirQuality->meta['color'] ?? '#EAB308',
            ];
        })->values();

        $hotspotsPayload = $activeReports->map(function (SmokeReport $rep) {
            return [
                'id' => $rep->id,
                'region' => $rep->region->name ?? 'Kediri Raya',
                'category' => $rep->category,
                'description' => $rep->description,
                'lat' => $rep->latitude,
                'lng' => $rep->longitude,
                'status' => $rep->status,
                'status_label' => $rep->status_label,
                'created_at' => $rep->created_at->diffForHumans(),
            ];
        })->values();

        return view('rute-bersih', [
            'regions' => $regions,
            'presets' => $this->airQualityService->getKediriRayaPresets(),
            'bounds' => AirQualityService::KEDIRI_RAYA_BOUNDS,
            'stationsPayload' => $stationsPayload,
            'hotspotsPayload' => $hotspotsPayload,
            'activeReports' => $activeReports,
        ]);
    }
}
