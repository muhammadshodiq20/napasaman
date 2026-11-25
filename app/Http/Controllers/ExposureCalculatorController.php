<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Services\AirQualityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExposureCalculatorController extends Controller
{
    public function __construct(
        protected AirQualityService $airQualityService
    ) {}

    public function index(Request $request): View
    {
        $regions = $this->airQualityService->getRegionsWithLatestAirQuality();

        $selectedSlug = $request->query('region', 'kota-kediri');
        $selectedRegion = $regions->firstWhere('slug', $selectedSlug) ?? $regions->first();
        $activity = $request->query('activity', 'motor');
        $durationMinutes = max(5, min(720, (int) $request->query('duration', 60)));

        $initialResult = $this->airQualityService->calculateExposure(
            (float) ($selectedRegion->latestAirQuality->pm25 ?? 20.0),
            $activity,
            $durationMinutes
        );

        $regionsData = $regions->map(function (Region $r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'pm25' => round((float) ($r->latestAirQuality->pm25 ?? 18.0), 1),
                'aqi' => (int) ($r->latestAirQuality->aqi ?? 60),
                'category' => $r->latestAirQuality->category ?? 'Sedang',
            ];
        })->values();

        return view('kalkulator-paparan', [
            'regions' => $regions,
            'regionsData' => $regionsData,
            'selectedRegion' => $selectedRegion,
            'activity' => $activity,
            'durationMinutes' => $durationMinutes,
            'initialResult' => $initialResult,
        ]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region_slug' => ['required', 'string', 'exists:regions,slug'],
            'activity' => ['required', 'in:motor,jalan_kaki,jogging,dalam_rumah'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
        ]);

        $regions = $this->airQualityService->getRegionsWithLatestAirQuality();
        $region = $regions->firstWhere('slug', $validated['region_slug']);

        $result = $this->airQualityService->calculateExposure(
            (float) ($region->latestAirQuality->pm25 ?? 18.0),
            $validated['activity'],
            (int) $validated['duration_minutes']
        );

        return response()->json([
            'region' => $region->name,
            'aqi' => $region->latestAirQuality->aqi,
            'category' => $region->latestAirQuality->category,
            'result' => $result,
        ]);
    }
}
