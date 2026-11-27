<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\SmokeReport;
use App\Models\WhatsappLog;
use App\Services\AirQualityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected AirQualityService $airQualityService
    ) {}

    public function index(Request $request): View
    {
        $forceRefresh = $request->boolean('refresh');
        $regions = $this->airQualityService->getRegionsWithLatestAirQuality($forceRefresh);

        $selectedSlug = $request->query('region', 'kota-kediri');
        $selectedRegion = $regions->firstWhere('slug', $selectedSlug) ?? $regions->first();

        if (! $selectedRegion || ! $selectedRegion->latestAirQuality) {
            abort(503, 'Data kualitas udara wilayah belum tersedia.');
        }

        $hourlyTrend = $this->airQualityService->getHourlyTrend($selectedRegion);
        $bestJogging = $this->airQualityService->getBestJoggingWindow($hourlyTrend);
        $aq = $selectedRegion->latestAirQuality;
        $recommendations = $this->airQualityService->getSegmentRecommendations(
            (int) $aq->aqi,
            (float) $aq->pm25,
            $bestJogging
        );

        // Precompute hourly trends & recommendations for all 7 regions so client-side switching is instant
        $regionsPayload = $regions->mapWithKeys(function (Region $region) {
            $trend = $this->airQualityService->getHourlyTrend($region);
            $jog = $this->airQualityService->getBestJoggingWindow($trend);
            $log = $region->latestAirQuality;
            $recs = $this->airQualityService->getSegmentRecommendations((int) $log->aqi, (float) $log->pm25, $jog);

            return [
                $region->slug => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                    'latitude' => $region->latitude,
                    'longitude' => $region->longitude,
                    'aqi' => (int) $log->aqi,
                    'pm25' => round((float) $log->pm25, 1),
                    'pm10' => round((float) $log->pm10, 1),
                    'no2' => round((float) $log->no2, 1),
                    'category' => $log->category,
                    'source' => $log->source,
                    'recorded_at' => $log->recorded_at->format('d M Y, H:i') . ' WIB',
                    'meta' => $log->meta,
                    'trend' => $trend,
                    'jogging' => $jog,
                    'recommendations' => $recs,
                ],
            ];
        });

        $activeReportsCount = SmokeReport::whereIn('status', ['dilaporkan', 'diverifikasi'])->count();
        $totalWaInteractions = WhatsappLog::count();
        $avgAqi = (int) round($regions->avg(fn (Region $r) => $r->latestAirQuality->aqi ?? 50));

        return view('home', [
            'regions' => $regions,
            'selectedRegion' => $selectedRegion,
            'hourlyTrend' => $hourlyTrend,
            'bestJogging' => $bestJogging,
            'recommendations' => $recommendations,
            'regionsPayload' => $regionsPayload,
            'activeReportsCount' => $activeReportsCount,
            'totalWaInteractions' => $totalWaInteractions,
            'avgAqi' => $avgAqi,
        ]);
    }

    public function regionJson(Region $region): JsonResponse
    {
        $regions = $this->airQualityService->getRegionsWithLatestAirQuality();
        $target = $regions->firstWhere('id', $region->id) ?? $region->load('latestAirQuality');
        $trend = $this->airQualityService->getHourlyTrend($target);
        $jog = $this->airQualityService->getBestJoggingWindow($trend);
        $aq = $target->latestAirQuality;

        $aqi = (int) ($aq->aqi ?? 60);
        $pm25 = (float) ($aq->pm25 ?? 18.0);
        $pm10 = (float) ($aq->pm10 ?? 28.0);
        $no2 = (float) ($aq->no2 ?? 10.0);
        $category = $aq->category ?? 'Sedang';

        return response()->json([
            'region' => $target->name,
            'aqi' => $aqi,
            'pm25' => $pm25,
            'pm10' => $pm10,
            'no2' => $no2,
            'category' => $category,
            'trend' => $trend,
            'jogging' => $jog,
            'recommendations' => $this->airQualityService->getSegmentRecommendations($aqi, $pm25, $jog),
        ]);
    }
}
