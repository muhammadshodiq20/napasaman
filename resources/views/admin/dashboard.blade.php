@extends('layouts.app')

@section('title', 'Panel Admin & Mitra RT/RW — NapasAman Kediri Raya')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full bg-[#0B3566] text-white text-xs font-bold uppercase">{{ auth()->user()->role }}</span>
                <span class="text-xs font-semibold text-slate-500">{{ auth()->user()->name }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0B3566] mt-2">Dashboard KPI Dampak &amp; Tindak Lanjut</h1>
            <p class="text-sm text-slate-600 mt-0.5">Monitoring interaksi WA Bot Sobat Napas &amp; pengelolaan laporan titik asap RT/RW dan Bank Sampah se-Kediri Raya.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('rute-bersih') }}" class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:border-[#38A8E8] text-[#0B3566] text-xs font-bold transition">
                🗺️ Peta Hotspot
            </a>
            <a href="{{ route('bot-wa.index') }}" class="px-4 py-2.5 rounded-xl bg-[#2E9E3F] hover:bg-[#237E31] text-white text-xs font-bold transition">
                💬 Uji WA Bot
            </a>
        </div>
    </div>

    <!-- KPI Impact Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-xs font-bold text-slate-500 uppercase">Interaksi WA Bot</p>
            <p class="text-3xl font-extrabold text-[#0B3566] mt-1">{{ $totalWaInteractions }}</p>
            <p class="text-xs text-[#2E9E3F] font-semibold mt-1">{{ $uniqueWaUsers }} nomor warga unik aktif</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-xs font-bold text-slate-500 uppercase">Total Laporan Asap</p>
            <p class="text-3xl font-extrabold text-[#0B3566] mt-1">{{ $totalReports }}</p>
            <p class="text-xs text-slate-500 mt-1">Tersebar di 7 wilayah Kediri Raya</p>
        </div>

        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-5 shadow-sm">
            <p class="text-xs font-bold text-amber-800 uppercase">Perlu Tindakan</p>
            <p class="text-3xl font-extrabold text-amber-900 mt-1">{{ $statusCounts['dilaporkan'] + $statusCounts['diverifikasi'] }}</p>
            <p class="text-xs text-amber-700 font-semibold mt-1">
                {{ $statusCounts['dilaporkan'] }} Dilaporkan • {{ $statusCounts['diverifikasi'] }} Diverifikasi
            </p>
        </div>

        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 shadow-sm">
            <p class="text-xs font-bold text-emerald-800 uppercase">Sudah Ditindaklanjuti</p>
            <p class="text-3xl font-extrabold text-[#2E9E3F] mt-1">{{ $statusCounts['ditindaklanjuti'] }}</p>
            @php
                $pct = $totalReports > 0 ? round(($statusCounts['ditindaklanjuti'] / $totalReports) * 100) : 0;
            @endphp
            <p class="text-xs text-emerald-700 font-semibold mt-1">{{ $pct }}% tingkat penyelesaian edukatif</p>
        </div>
    </section>

    <!-- Chart: Reports per Region + Status Summary -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base sm:text-lg font-extrabold text-[#0B3566]">Sebaran Laporan Titik Asap per Wilayah</h2>
                    <p class="text-xs text-slate-500">Perbandingan laporan aktif vs sudah ditindaklanjuti pada 7 daerah Eks-Karesidenan Kediri.</p>
                </div>
            </div>
            <div class="h-64 w-full">
                <canvas id="regionReportsChart"></canvas>
            </div>
        </div>

        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-4">
            <h2 class="text-base sm:text-lg font-extrabold text-[#0B3566]">Ringkasan Status Penanganan</h2>
            <div class="space-y-3">
                <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-red-700">Dilaporkan</p>
                        <p class="text-[11px] text-red-600">Menunggu pengecekan lapangan</p>
                    </div>
                    <span class="text-2xl font-extrabold text-red-700">{{ $statusCounts['dilaporkan'] }}</span>
                </div>
                <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-amber-800">Diverifikasi</p>
                        <p class="text-[11px] text-amber-700">Tervalidasi oleh RT/RW &amp; relawan</p>
                    </div>
                    <span class="text-2xl font-extrabold text-amber-800">{{ $statusCounts['diverifikasi'] }}</span>
                </div>
                <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-emerald-800">Ditindaklanjuti</p>
                        <p class="text-[11px] text-emerald-700">Edukasi warga / dialihkan ke Bank Sampah</p>
                    </div>
                    <span class="text-2xl font-extrabold text-[#2E9E3F]">{{ $statusCounts['ditindaklanjuti'] }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Smoke Reports Management Table -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-[#0B3566]">Kelola Laporan Titik Asap &amp; Tindak Lanjut Edukatif</h2>
                <p class="text-xs text-slate-500">Perbarui status penanganan dan catat hasil edukasi RT/RW atau koordinasi Bank Sampah.</p>
            </div>

            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap gap-2">
                <select name="region_id" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-[#0B3566]">
                    <option value="">Semua Wilayah</option>
                    @foreach ($reportsByRegion as $reg)
                        <option value="{{ $reg->id }}" {{ request('region_id') == $reg->id ? 'selected' : '' }}>{{ $reg->name }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-[#0B3566]">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs sm:text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold text-[#0B3566] uppercase">
                    <tr>
                        <th class="px-5 py-3.5">Laporan &amp; Wilayah</th>
                        <th class="px-5 py-3.5">Deskripsi &amp; Pelapor</th>
                        <th class="px-5 py-3.5">Status Saat Ini</th>
                        <th class="px-5 py-3.5 min-w-[300px]">Ubah Status &amp; Catatan Tindak Lanjut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-slate-50/70 align-top">
                            <td class="px-5 py-4 space-y-1">
                                <span class="font-extrabold text-[#0B3566]">#NA-{{ $report->id }}</span>
                                <p class="font-bold text-slate-800">{{ $report->region->name ?? '-' }}</p>
                                <span class="inline-block px-2 py-0.5 rounded-full bg-orange-50 text-orange-700 border border-orange-200 text-[11px] font-bold">
                                    {{ $report->category }}
                                </span>
                                <p class="text-[11px] text-slate-400">{{ $report->created_at->format('d M Y, H:i') }}</p>
                            </td>
                            <td class="px-5 py-4 space-y-1.5 max-w-md">
                                <p class="text-slate-700 leading-relaxed">{{ $report->description }}</p>
                                <p class="text-[11px] text-slate-500">
                                    Pelapor: <strong>{{ $report->reporter_name }}</strong>
                                    @if ($report->reporter_phone)
                                        ({{ $report->reporter_phone }})
                                    @endif
                                </p>
                                @if ($report->photo_url)
                                    <a href="{{ $report->photo_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#38A8E8] hover:underline">
                                        📷 Lihat Foto Bukti
                                    </a>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold border {{ $report->status_badge_class }}">
                                    {{ $report->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.reports.update-status', $report) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex gap-2">
                                        <select name="status" class="flex-1 rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-[#0B3566]">
                                            @foreach ($statuses as $key => $label)
                                                <option value="{{ $key }}" {{ $report->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-[#2E9E3F] hover:bg-[#237E31] text-white text-xs font-bold transition shrink-0">
                                            Simpan
                                        </button>
                                    </div>
                                    <input type="text" name="admin_notes" value="{{ $report->admin_notes }}"
                                           placeholder="Catat tindak lanjut RT/RW atau Bank Sampah..."
                                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs focus:border-[#38A8E8] focus:bg-white focus:outline-none">
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-500">Belum ada data laporan untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $reports->links() }}
        </div>
    </section>

    <!-- Recent WhatsApp Bot Logs -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100">
            <h2 class="text-lg font-extrabold text-[#0B3566]">Log Interaksi Terbaru WhatsApp Bot "Sobat Napas"</h2>
            <p class="text-xs text-slate-500">10 percakapan terakhir yang masuk melalui webhook resmi WhatsApp Bot Sobat Napas (<code>/api/whatsapp/webhook</code>).</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-left font-bold text-[#0B3566] uppercase">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Pengirim</th>
                        <th class="px-5 py-3">Perintah</th>
                        <th class="px-5 py-3">Pesan Masuk</th>
                        <th class="px-5 py-3">Ringkasan Balasan Bot</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($recentWaLogs as $log)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500">{{ $log->created_at->format('d M, H:i') }}</td>
                            <td class="px-5 py-3 font-mono text-slate-700">{{ $log->sender_phone }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full bg-sky-50 text-[#0B3566] border border-sky-200 font-bold">{{ $log->command }}</span>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $log->incoming_message }}</td>
                            <td class="px-5 py-3 text-slate-600 max-w-md truncate">{{ \Illuminate\Support\Str::limit(str_replace("\n", ' ', $log->bot_reply), 110) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const REGION_STATS = @json($regionChartStats);

    const ctx = document.getElementById('regionReportsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: REGION_STATS.map(r => r.name),
            datasets: [
                {
                    label: 'Aktif (Dilaporkan / Diverifikasi)',
                    data: REGION_STATS.map(r => r.active),
                    backgroundColor: '#F97316',
                    borderRadius: 8,
                },
                {
                    label: 'Ditindaklanjuti',
                    data: REGION_STATS.map(r => r.resolved),
                    backgroundColor: '#2E9E3F',
                    borderRadius: 8,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' } } }
            },
            scales: {
                x: { stacked: false, grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#F1F5F9' } }
            }
        }
    });
</script>
@endpush
