<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\SmokeReport;
use App\Services\AirQualityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmokeReportController extends Controller
{
    public function index(Request $request): View
    {
        $regions = Region::where('is_active', true)->orderBy('id')->get();

        $query = SmokeReport::with('region')->latest();

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }

        if ($request->filled('status') && array_key_exists($request->string('status')->toString(), SmokeReport::STATUSES)) {
            $query->where('status', $request->string('status')->toString());
        }

        $reports = $query->paginate(9)->withQueryString();

        $activeMapReports = SmokeReport::with('region')
            ->whereIn('status', ['dilaporkan', 'diverifikasi'])
            ->latest()
            ->get()
            ->map(fn (SmokeReport $r) => [
                'id' => $r->id,
                'region' => $r->region->name ?? '-',
                'category' => $r->category,
                'description' => $r->description,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'status_label' => $r->status_label,
                'created_at' => $r->created_at->diffForHumans(),
            ])->values();

        return view('lapor-asap', [
            'regions' => $regions,
            'categories' => SmokeReport::CATEGORIES,
            'statuses' => SmokeReport::STATUSES,
            'reports' => $reports,
            'activeMapReports' => $activeMapReports,
            'bounds' => AirQualityService::KEDIRI_RAYA_BOUNDS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $bounds = AirQualityService::KEDIRI_RAYA_BOUNDS;

        $validated = $request->validate([
            'region_id' => ['required', 'exists:regions,id'],
            'reporter_name' => ['required', 'string', 'max:100'],
            'reporter_phone' => ['nullable', 'string', 'max:25'],
            'category' => ['required', 'in:' . implode(',', SmokeReport::CATEGORIES)],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'latitude' => ['required', 'numeric', "between:{$bounds['south']},{$bounds['north']}"],
            'longitude' => ['required', 'numeric', "between:{$bounds['west']},{$bounds['east']}"],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'latitude.between' => 'Lokasi harus berada di dalam wilayah Eks-Karesidenan Kediri.',
            'longitude.between' => 'Lokasi harus berada di dalam wilayah Eks-Karesidenan Kediri.',
            'description.min' => 'Mohon tuliskan deskripsi singkat minimal 10 karakter.',
            'photo.max' => 'Ukuran foto bukti maksimal 3 MB.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('smoke-reports', 'public');
        }

        $report = SmokeReport::create([
            'region_id' => $validated['region_id'],
            'reporter_name' => $validated['reporter_name'],
            'reporter_phone' => $validated['reporter_phone'] ?? null,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'photo_path' => $photoPath,
            'status' => 'dilaporkan',
        ]);

        return redirect()
            ->route('lapor-asap.index')
            ->with('success', "Terima kasih! Laporan titik asap #NA-{$report->id} berhasil dikirim dan langsung tampil pada Peta Rute Bersih.");
    }
}
