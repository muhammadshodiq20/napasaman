<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\SmokeReport;
use App\Models\WhatsappLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! Auth::user()->isAdminOrMitra()) {
            return redirect()->route('home')->with('error', 'Akses panel hanya untuk Admin atau Mitra RT/RW & Bank Sampah.');
        }

        $totalWaInteractions = WhatsappLog::count();
        $uniqueWaUsers = WhatsappLog::distinct('sender_phone')->count('sender_phone');
        $totalReports = SmokeReport::count();

        $statusCounts = [
            'dilaporkan' => SmokeReport::where('status', 'dilaporkan')->count(),
            'diverifikasi' => SmokeReport::where('status', 'diverifikasi')->count(),
            'ditindaklanjuti' => SmokeReport::where('status', 'ditindaklanjuti')->count(),
        ];

        $reportsByRegion = Region::withCount('smokeReports')
            ->withCount(['smokeReports as active_reports_count' => fn ($q) => $q->whereIn('status', ['dilaporkan', 'diverifikasi'])])
            ->withCount(['smokeReports as resolved_reports_count' => fn ($q) => $q->where('status', 'ditindaklanjuti')])
            ->orderBy('id')
            ->get();

        $reportsQuery = SmokeReport::with('region')->latest();
        if ($request->filled('region_id')) {
            $reportsQuery->where('region_id', $request->integer('region_id'));
        }
        if ($request->filled('status') && array_key_exists($request->string('status')->toString(), SmokeReport::STATUSES)) {
            $reportsQuery->where('status', $request->string('status')->toString());
        }

        $reports = $reportsQuery->paginate(10)->withQueryString();
        $recentWaLogs = WhatsappLog::latest()->limit(10)->get();

        $regionChartStats = $reportsByRegion->map(fn (Region $r) => [
            'name' => str_replace(['Kabupaten ', 'Kota '], ['Kab. ', 'Kota '], $r->name),
            'active' => (int) $r->active_reports_count,
            'resolved' => (int) $r->resolved_reports_count,
        ])->values();

        return view('admin.dashboard', [
            'totalWaInteractions' => $totalWaInteractions,
            'uniqueWaUsers' => $uniqueWaUsers,
            'totalReports' => $totalReports,
            'statusCounts' => $statusCounts,
            'reportsByRegion' => $reportsByRegion,
            'regionChartStats' => $regionChartStats,
            'reports' => $reports,
            'recentWaLogs' => $recentWaLogs,
            'statuses' => SmokeReport::STATUSES,
        ]);
    }

    public function updateReportStatus(Request $request, SmokeReport $smokeReport): RedirectResponse
    {
        if (! Auth::user()->isAdminOrMitra()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:dilaporkan,diverifikasi,ditindaklanjuti'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $smokeReport->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $smokeReport->admin_notes,
        ]);

        return back()->with('success', "Status laporan #NA-{$smokeReport->id} diperbarui menjadi \"{$smokeReport->status_label}\".");
    }
}
