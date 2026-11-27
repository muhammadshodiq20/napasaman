@extends('layouts.app')

@section('title', 'Navigasi Real-Time Rute Bersih — NapasAman Kediri Raya')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #cleanRouteMap { height: 540px; width: 100%; border-radius: 1rem; z-index: 10; }
    @media (max-width: 768px) { #cleanRouteMap { height: 410px; } }
    .leaflet-popup-content-wrapper { border-radius: 14px; font-family: 'Plus Jakarta Sans', sans-serif; }
    @keyframes navPulse {
        0% { transform: scale(1); opacity: 0.75; }
        100% { transform: scale(2.4); opacity: 0; }
    }
    .nav-user-dot {
        position: relative;
        width: 22px;
        height: 22px;
    }
    .nav-user-dot::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        background: rgba(56, 168, 232, 0.55);
        animation: navPulse 1.6s ease-out infinite;
    }
    .nav-user-dot-core {
        position: relative;
        width: 22px;
        height: 22px;
        border-radius: 9999px;
        background: #0B3566;
        border: 3px solid #ffffff;
        box-shadow: 0 3px 12px rgba(11, 53, 102, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6CE07D;
        font-size: 11px;
        font-weight: 800;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-[#2E9E3F] text-xs font-bold border border-emerald-200">
                🧭 Navigasi Real-Time Open-Source • Leaflet + OSM + OSRM (100% Gratis)
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0B3566] mt-2">Navigasi Real-Time Rute Rendah Polusi</h1>
            <p class="text-sm text-slate-600 mt-1 max-w-3xl">
                Bukan sekadar rekomendasi statis — ikuti perjalanan Anda secara <strong>real-time dengan panduan belokan (turn-by-turn), suara Bahasa Indonesia, pelacakan GPS live, &amp; pemantauan PM2.5 per ruas jalan</strong> khusus wilayah Eks-Karesidenan Kediri.
            </p>
        </div>
        <a href="{{ route('lapor-asap.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-orange-50 hover:bg-orange-100 text-orange-700 border border-orange-200 text-xs font-bold transition shrink-0">
            🔥 {{ $activeReports->count() }} Titik Asap Aktif • Tambah Laporan
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Controls, User Profile & Route Comparison -->
        <div class="lg:col-span-4 space-y-4">

            <!-- User Profile + Origin/Destination Picker Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-extrabold text-[#0B3566]">Pengaturan Perjalanan</h2>
                    <span class="text-[11px] font-semibold text-[#38A8E8] bg-sky-50 px-2.5 py-1 rounded-lg">Area Kediri Raya</span>
                </div>

                <!-- User Segment Priority Selector -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-[#0B3566]">Profil Pengguna</label>
                    <div class="grid grid-cols-3 gap-1.5 p-1 rounded-xl bg-slate-100" id="profileSelector">
                        <button type="button" data-profile="komuter"
                                class="profile-btn px-2 py-2 rounded-lg text-[11px] font-bold bg-[#0B3566] text-white shadow transition text-center leading-tight">
                            🛵 Kantoran &amp; Komuter
                        </button>
                        <button type="button" data-profile="mahasiswa"
                                class="profile-btn px-2 py-2 rounded-lg text-[11px] font-bold text-slate-600 hover:text-[#0B3566] transition text-center leading-tight">
                            🎓 Mahasiswa &amp; Sepeda
                        </button>
                        <button type="button" data-profile="pelari"
                                class="profile-btn px-2 py-2 rounded-lg text-[11px] font-bold text-slate-600 hover:text-[#0B3566] transition text-center leading-tight">
                            🏃 Pelari &amp; Keluarga
                        </button>
                    </div>
                    <p id="profileDesc" class="text-[11px] text-slate-500">
                        Prioritas rute cepat &amp; bebas asap knalpot pekat untuk pekerja kantoran &amp; pengendara motor.
                    </p>
                </div>

                <!-- Start Point -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="startPreset" class="block text-xs font-bold text-[#0B3566]">Titik Awal (A)</label>
                        <button type="button" id="btnUseMyGpsStart"
                                class="text-[11px] font-bold text-[#38A8E8] hover:underline">
                            📍 Pakai GPS Saya
                        </button>
                    </div>
                    <select id="startPreset" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-800 focus:border-[#38A8E8] focus:bg-white focus:outline-none">
                        @foreach ($presets as $idx => $p)
                            <option value="{{ $p['lat'] }},{{ $p['lng'] }}" {{ $idx === 1 ? 'selected' : '' }}>
                                {{ $p['name'] }} ({{ $p['region'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- End Point -->
                <div class="space-y-1.5">
                    <label for="endPreset" class="block text-xs font-bold text-[#0B3566]">Titik Tujuan (B)</label>
                    <select id="endPreset" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-800 focus:border-[#38A8E8] focus:bg-white focus:outline-none">
                        @foreach ($presets as $idx => $p)
                            <option value="{{ $p['lat'] }},{{ $p['lng'] }}" {{ $idx === 0 ? 'selected' : '' }}>
                                {{ $p['name'] }} ({{ $p['region'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Map Click Mode Buttons -->
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" id="btnPickStart"
                            class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-sky-50 hover:border-[#38A8E8] text-xs font-bold text-[#0B3566] transition">
                        📍 Klik Peta: Awal
                    </button>
                    <button type="button" id="btnPickEnd"
                            class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-[#2E9E3F] text-xs font-bold text-[#0B3566] transition">
                        🏁 Klik Peta: Tujuan
                    </button>
                </div>
                <p id="pickModeHint" class="text-[11px] text-slate-600 hidden bg-sky-50 border border-sky-200 rounded-xl px-3 py-2"></p>

                <button type="button" id="btnCalculateRoute"
                        class="w-full py-2.5 px-4 rounded-xl bg-[#0B3566] hover:bg-[#08274C] text-white font-bold text-sm shadow-sm transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Hitung Ulang Rute Terbersih
                </button>
            </div>

            <!-- Real-Time Navigation Launcher Card -->
            <div class="bg-gradient-to-br from-[#0B3566] to-[#145091] text-white rounded-2xl p-5 shadow-lg space-y-3.5">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-sky-200">Navigasi Live Open-Source</p>
                        <h3 class="text-base font-extrabold">Mulai Navigasi Real-Time</h3>
                    </div>
                    <button type="button" id="btnToggleVoice"
                            class="px-2.5 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-xs font-bold transition"
                            title="Aktifkan/Matikan Panduan Suara Bahasa Indonesia">
                        🔊 Suara ON
                    </button>
                </div>

                <p class="text-xs text-sky-100/90 leading-relaxed">
                    Gunakan <strong>GPS Live</strong> saat di jalan, atau <strong>Simulasi Jalan Real-Time</strong> untuk demo pergerakan langsung di peta lengkap dengan instruksi belokan &amp; sensor PM2.5 per ruas.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button type="button" id="btnStartGpsNav"
                            class="py-2.5 px-3 rounded-xl bg-[#2E9E3F] hover:bg-[#237E31] text-white text-xs font-extrabold shadow transition flex items-center justify-center gap-1.5">
                        🛰️ GPS Live
                    </button>
                    <button type="button" id="btnStartSimNav"
                            class="py-2.5 px-3 rounded-xl bg-[#38A8E8] hover:bg-[#1F8FD0] text-white text-xs font-extrabold shadow transition flex items-center justify-center gap-1.5">
                        ▶ Simulasi Jalan
                    </button>
                </div>

                <!-- Simulation Speed & Stop Bar (visible when navigating) -->
                <div id="navActiveControls" class="hidden pt-2 border-t border-white/15 flex items-center justify-between gap-2">
                    <div id="simSpeedControls" class="flex items-center gap-1.5 text-[11px]">
                        <span class="text-sky-200 font-semibold">Kecepatan:</span>
                        <button type="button" data-speed="1" class="sim-speed-btn px-2 py-1 rounded-lg bg-white text-[#0B3566] font-extrabold">1x</button>
                        <button type="button" data-speed="2" class="sim-speed-btn px-2 py-1 rounded-lg bg-white/15 text-white font-bold">2x</button>
                        <button type="button" data-speed="4" class="sim-speed-btn px-2 py-1 rounded-lg bg-white/15 text-white font-bold">4x</button>
                    </div>
                    <button type="button" id="btnStopNav"
                            class="ml-auto px-3 py-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white text-xs font-extrabold transition">
                        ■ Berhenti
                    </button>
                </div>
            </div>

            <!-- Automatic Smoke Hotspot Warning Banner -->
            <div id="smokeWarningBox" class="hidden rounded-2xl bg-red-50 border border-red-200 p-4 space-y-2 shadow-sm">
                <div class="flex items-start gap-2.5">
                    <span class="w-7 h-7 rounded-full bg-red-600 text-white flex items-center justify-center text-sm font-bold shrink-0">!</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-red-800">Peringatan Titik Asap Aktif!</h3>
                        <p id="smokeWarningText" class="text-xs text-red-700 mt-0.5 leading-relaxed"></p>
                    </div>
                </div>
            </div>

            <!-- Comparison Results (Click either card to choose active route for navigation) -->
            <div id="routeResultContainer" class="space-y-3">
                <!-- Cleanest Route Card -->
                <div id="cardCleanRoute" onclick="selectActiveRoute('clean')"
                     class="cursor-pointer rounded-2xl bg-white border-2 border-[#2E9E3F] p-4 shadow-sm space-y-2.5 transition">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-[#2E9E3F]">
                            <span class="w-3 h-3 rounded-full bg-[#2E9E3F]"></span>
                            <span id="cleanRouteTitle">RUTE TERBERSIH (DIPILIH)</span>
                        </span>
                        <span id="cleanSavingsBadge" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold">Lebih Bersih</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-emerald-50/70 p-2">
                            <p class="text-[11px] text-slate-500 font-semibold">Jarak</p>
                            <p id="cleanDistance" class="text-sm font-extrabold text-[#0B3566]">—</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50/70 p-2">
                            <p class="text-[11px] text-slate-500 font-semibold">Waktu</p>
                            <p id="cleanDuration" class="text-sm font-extrabold text-[#0B3566]">—</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50/70 p-2">
                            <p class="text-[11px] text-slate-500 font-semibold">Paparan PM2.5</p>
                            <p id="cleanExposure" class="text-sm font-extrabold text-[#2E9E3F]">—</p>
                        </div>
                    </div>
                    <p id="cleanNote" class="text-xs text-slate-600"></p>
                </div>

                <!-- Fastest Route Card -->
                <div id="cardFastRoute" onclick="selectActiveRoute('fast')"
                     class="cursor-pointer rounded-2xl bg-white border border-slate-200 hover:border-[#38A8E8] p-4 shadow-sm space-y-2.5 transition">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-[#0B3566]">
                            <span class="w-3 h-3 rounded-full bg-[#38A8E8]"></span>
                            <span id="fastRouteTitle">RUTE TERCEPAT (STANDAR)</span>
                        </span>
                        <span id="fastestHotspotBadge" class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold">Standar</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-slate-50 p-2">
                            <p class="text-[11px] text-slate-500 font-semibold">Jarak</p>
                            <p id="fastDistance" class="text-sm font-extrabold text-[#0B3566]">—</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-2">
                            <p class="text-[11px] text-slate-500 font-semibold">Waktu</p>
                            <p id="fastDuration" class="text-sm font-extrabold text-[#0B3566]">—</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-2">
                            <p class="text-[11px] text-slate-500 font-semibold">Paparan PM2.5</p>
                            <p id="fastExposure" class="text-sm font-extrabold text-orange-600">—</p>
                        </div>
                    </div>
                    <p id="fastNote" class="text-xs text-slate-600"></p>
                </div>
            </div>
        </div>

        <!-- Right Map, Live Turn-by-Turn HUD & Steps List -->
        <div class="lg:col-span-8 space-y-4">

            <!-- Turn-by-Turn Live Navigation HUD Banner (shown during active GPS/Simulation navigation) -->
            <div id="liveNavHud" class="hidden rounded-2xl bg-[#0B3566] text-white p-4 sm:p-5 shadow-xl border-2 border-[#2E9E3F] space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3.5">
                        <div id="hudManeuverIcon" class="w-12 h-12 rounded-2xl bg-[#2E9E3F] text-white flex items-center justify-center text-2xl font-extrabold shrink-0 shadow">
                            ⬆
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span id="hudModeBadge" class="px-2 py-0.5 rounded-full bg-[#38A8E8] text-white text-[10px] font-extrabold uppercase">NAVIGASI REAL-TIME</span>
                                <span id="hudNextStepDist" class="text-xs font-bold text-[#6CE07D]">—</span>
                            </div>
                            <h3 id="hudInstruction" class="text-base sm:text-lg font-extrabold leading-snug mt-0.5">
                                Bersiap memulai perjalanan...
                            </h3>
                        </div>
                    </div>
                    <button type="button" id="btnRecenterMap"
                            class="shrink-0 px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-xs font-bold transition">
                        🎯 Ikuti Posisi
                    </button>
                </div>

                <!-- Live Telemetry Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-center">
                    <div class="rounded-xl bg-white/10 px-3 py-2">
                        <p class="text-[10px] text-sky-200 font-semibold uppercase">Sisa Jarak</p>
                        <p id="hudRemainDist" class="text-sm font-extrabold">—</p>
                    </div>
                    <div class="rounded-xl bg-white/10 px-3 py-2">
                        <p class="text-[10px] text-sky-200 font-semibold uppercase">Sisa Waktu</p>
                        <p id="hudRemainTime" class="text-sm font-extrabold">—</p>
                    </div>
                    <div class="rounded-xl bg-white/10 px-3 py-2">
                        <p class="text-[10px] text-sky-200 font-semibold uppercase">PM2.5 Ruas Ini</p>
                        <p id="hudLivePm25" class="text-sm font-extrabold text-[#6CE07D]">—</p>
                    </div>
                    <div class="rounded-xl bg-white/10 px-3 py-2">
                        <p class="text-[10px] text-sky-200 font-semibold uppercase">Terhirup Live</p>
                        <p id="hudAccumulatedUg" class="text-sm font-extrabold text-sky-200">0.0 µg</p>
                    </div>
                </div>

                <!-- Live Proximity Smoke Alert Inside HUD -->
                <div id="hudSmokeAlert" class="hidden rounded-xl bg-red-600/90 border border-red-300 px-3.5 py-2 text-xs font-bold flex items-center gap-2">
                    <span>⚠️</span>
                    <span id="hudSmokeAlertText"></span>
                </div>
            </div>

            <!-- Interactive Map Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-3 sm:p-4 shadow-sm space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex flex-wrap items-center gap-3 font-semibold text-slate-600">
                        <span class="inline-flex items-center gap-1.5"><span class="w-4 h-1.5 rounded bg-[#2E9E3F]"></span> Rute Terbersih</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-4 h-1.5 rounded bg-[#38A8E8]"></span> Rute Tercepat</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span> Titik Asap Warga</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-[#0B3566]"></span> Stasiun AQI (7 Wilayah)</span>
                    </div>
                    <span id="routingStatus" class="text-[11px] font-bold text-[#38A8E8]">Memuat rute...</span>
                </div>

                <div id="cleanRouteMap"></div>
            </div>

            <!-- Turn-by-Turn Directions Panel (OSRM Steps) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-extrabold text-[#0B3566]">Panduan Belokan (Turn-by-Turn OpenStreetMap)</h3>
                        <p class="text-xs text-slate-500">Diperbarui otomatis mengikuti rute terpilih &amp; posisi perjalanan Anda.</p>
                    </div>
                    <span id="stepsCountBadge" class="px-2.5 py-1 rounded-full bg-slate-100 text-[#0B3566] text-[11px] font-bold">0 langkah</span>
                </div>
                <ol id="turnStepsList" class="max-h-56 overflow-y-auto divide-y divide-slate-100 text-xs space-y-1 pr-1">
                    <li class="py-2 text-slate-400">Menunggu perhitungan rute...</li>
                </ol>
            </div>

            <!-- Active Smoke Hotspots List Preview -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-[#0B3566]">Daftar Titik Asap Aktif di Peta ({{ $activeReports->count() }})</h3>
                    <a href="{{ route('lapor-asap.index') }}" class="text-xs font-bold text-[#38A8E8] hover:underline">Lihat semua laporan →</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @forelse ($activeReports->take(4) as $rep)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 flex items-start justify-between gap-2">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">{{ $rep->category }}</span>
                                    <span class="text-[11px] font-bold text-[#0B3566]">{{ $rep->region->name ?? '' }}</span>
                                </div>
                                <p class="text-xs text-slate-600 line-clamp-2">{{ $rep->description }}</p>
                            </div>
                            <button type="button"
                                    onclick="focusHotspot({{ $rep->latitude }}, {{ $rep->longitude }})"
                                    class="shrink-0 px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:border-[#38A8E8] text-[11px] font-bold text-[#0B3566]">
                                Lihat
                            </button>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 col-span-2">Belum ada titik asap aktif saat ini. Udara Kediri Raya terpantau aman!</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const BOUNDS = @json($bounds);
    const STATIONS = @json($stationsPayload);
    const HOTSPOTS = @json($hotspotsPayload);

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    const PROFILES = {
        komuter: {
            label: 'Kantoran & Komuter',
            osrmProfile: 'driving',
            breathingRate: 0.85,
            speedKmh: 36,
            detourFactor: 0.20,
            desc: 'Prioritas rute cepat & bebas asap knalpot pekat untuk pekerja kantoran & pengendara motor.'
        },
        mahasiswa: {
            label: 'Mahasiswa & Pesepeda',
            osrmProfile: 'driving',
            breathingRate: 1.55,
            speedKmh: 20,
            detourFactor: 0.25,
            desc: 'Menghindari jalan arteri macet dengan paparan emisi tinggi; ideal bagi mahasiswa & pesepeda.'
        },
        pelari: {
            label: 'Pelari & Keluarga',
            osrmProfile: 'foot',
            breathingRate: 2.60,
            speedKmh: 9,
            detourFactor: 0.28,
            desc: 'Memaksimalkan jalur udara terbersih & jauh dari titik pembakaran sampah untuk olahraga & keluarga.'
        }
    };

    let activeProfileKey = 'komuter';
    let activeRouteChoice = 'clean'; // 'clean' | 'fast'
    let evaluatedRoutes = { clean: null, fast: null };

    const maxBounds = L.latLngBounds(
        [BOUNDS.south, BOUNDS.west],
        [BOUNDS.north, BOUNDS.east]
    );

    const map = L.map('cleanRouteMap', {
        center: [-7.82, 112.03],
        zoom: 12,
        minZoom: 9,
        maxZoom: 18,
        maxBounds: maxBounds,
        maxBoundsViscosity: 1.0
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors • NapasAman Real-Time Nav'
    }).addTo(map);

    L.rectangle(maxBounds, {
        color: '#0B3566',
        weight: 1.5,
        dashArray: '6,6',
        fillOpacity: 0.02,
        interactive: false
    }).addTo(map);

    // 7 Kediri Raya AQI Station Markers
    STATIONS.forEach((st) => {
        const safeColor = /^#[0-9A-Fa-f]{3,8}$/.test(String(st.color || '')) ? st.color : '#EAB308';
        const icon = L.divIcon({
            className: 'aqi-station-pin',
            html: `<div style="background:${safeColor};color:#fff;font-weight:800;font-size:11px;padding:3px 8px;border-radius:999px;border:2px solid #fff;box-shadow:0 3px 10px rgba(0,0,0,0.22);white-space:nowrap;">${escapeHtml(st.aqi)}</div>`,
            iconSize: [38, 24],
            iconAnchor: [19, 12]
        });

        L.marker([st.lat, st.lng], { icon })
            .addTo(map)
            .bindPopup(`
                <div style="min-width:170px">
                    <strong style="color:#0B3566;font-size:13px">${escapeHtml(st.name)}</strong><br>
                    <span style="font-size:12px">AQI: <b>${escapeHtml(st.aqi)}</b> (${escapeHtml(st.category)})</span><br>
                    <span style="font-size:12px">PM2.5: <b>${escapeHtml(st.pm25)} µg/m³</b></span>
                </div>
            `);
    });

    // Active Citizen Smoke Report Hotspots
    HOTSPOTS.forEach((h) => {
        L.circle([h.lat, h.lng], {
            radius: 700,
            color: '#DC2626',
            weight: 1.5,
            fillColor: '#EF4444',
            fillOpacity: 0.18
        }).addTo(map);

        const fireIcon = L.divIcon({
            className: 'smoke-hotspot-pin',
            html: `<div style="background:#DC2626;color:#fff;width:28px;height:28px;border-radius:999px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 3px 12px rgba(220,38,38,0.45);font-size:14px;">🔥</div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });

        L.marker([h.lat, h.lng], { icon: fireIcon })
            .addTo(map)
            .bindPopup(`
                <div style="min-width:200px">
                    <span style="background:#FEE2E2;color:#B91C1C;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700">${escapeHtml(h.category)}</span>
                    <div style="font-weight:800;color:#0B3566;margin-top:4px">${escapeHtml(h.region)}</div>
                    <p style="font-size:11px;color:#475569;margin:4px 0">${escapeHtml(h.description)}</p>
                    <div style="font-size:10px;color:#64748B">Status: <b>${escapeHtml(h.status_label)}</b> • ${escapeHtml(h.created_at)}</div>
                </div>
            `);
    });

    window.focusHotspot = function(lat, lng) {
        map.setView([lat, lng], 14, { animate: true });
    };

    let startCoords = parseCoord(document.getElementById('startPreset').value);
    let endCoords = parseCoord(document.getElementById('endPreset').value);
    let pickMode = null;

    let startMarker = null;
    let endMarker = null;
    let fastestLine = null;
    let cleanestLine = null;
    let traveledLine = null;
    let userLiveMarker = null;

    // Navigation state
    let navMode = null; // null | 'gps' | 'sim'
    let gpsWatchId = null;
    let simTimerId = null;
    let simSpeed = 1;
    let voiceEnabled = true;
    let autoFollow = true;
    let lastSpokenStepIdx = -1;
    let lastHotspotAlertId = null;
    let accumulatedUg = 0;
    let lastNavCoordIdx = null;
    let lastLivePos = null;
    let lastRerouteAt = 0;
    let isRerouting = false;

    function parseCoord(val) {
        const [lat, lng] = val.split(',').map(Number);
        return { lat, lng };
    }

    function haversineKm(lat1, lng1, lat2, lng2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function localPm25At(lat, lng, isCleanCorridor = true) {
        let weightTotal = 0;
        let valTotal = 0;
        STATIONS.forEach((st) => {
            const d = Math.max(0.2, haversineKm(lat, lng, st.lat, st.lng));
            const w = 1 / (d * d);
            weightTotal += w;
            valTotal += w * st.pm25;
        });
        let localPm = (valTotal / weightTotal) * (isCleanCorridor ? 0.84 : 1.18);
        let nearestHotspot = null;
        let nearestDist = 999;

        HOTSPOTS.forEach((h) => {
            const dist = haversineKm(lat, lng, h.lat, h.lng);
            if (dist < nearestDist) {
                nearestDist = dist;
                nearestHotspot = h;
            }
            if (dist <= 0.95) {
                localPm += (1 - dist / 0.95) * 34;
            }
        });

        return {
            pm25: Number(localPm.toFixed(1)),
            nearHotspot: nearestDist <= 0.85 ? { ...nearestHotspot, distM: Math.round(nearestDist * 1000) } : null
        };
    }

    function speakId(text) {
        if (!voiceEnabled || !('speechSynthesis' in window)) return;
        try {
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            utter.lang = 'id-ID';
            utter.rate = 1.05;
            window.speechSynthesis.speak(utter);
        } catch {}
    }

    function translateManeuver(step) {
        const type = step.maneuver?.type || 'continue';
        const mod = step.maneuver?.modifier || '';
        const road = step.name && step.name.trim() !== '' ? ` ke ${step.name}` : '';
        const onRoad = step.name && step.name.trim() !== '' ? ` di ${step.name}` : '';

        if (type === 'depart') return { icon: '🚀', text: `Mulai perjalanan${onRoad}` };
        if (type === 'arrive') return { icon: '🏁', text: 'Anda telah tiba di tujuan!' };
        if (type === 'roundabout' || type === 'rotary') {
            const exit = step.maneuver?.exit ? ` ambil keluar ke-${step.maneuver.exit}` : '';
            return { icon: '↺', text: `Masuk bundaran${exit}${road}` };
        }
        if (mod.includes('right')) {
            return { icon: mod.includes('slight') ? '↗' : '↱', text: `${mod.includes('slight') ? 'Serong kanan' : 'Belok kanan'}${road}` };
        }
        if (mod.includes('left')) {
            return { icon: mod.includes('slight') ? '↖' : '↰', text: `${mod.includes('slight') ? 'Serong kiri' : 'Belok kiri'}${road}` };
        }
        if (mod === 'uturn') return { icon: '↩', text: `Putar balik${road}` };
        return { icon: '⬆', text: `Lurus terus${onRoad}` };
    }

    function updateEndpointMarkers() {
        if (startMarker) map.removeLayer(startMarker);
        if (endMarker) map.removeLayer(endMarker);

        const startIcon = L.divIcon({
            html: `<div style="background:#0B3566;color:#fff;font-weight:800;font-size:11px;width:28px;height:28px;border-radius:999px;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;box-shadow:0 3px 10px rgba(11,53,102,0.4)">A</div>`,
            className: '', iconSize: [28, 28], iconAnchor: [14, 14]
        });
        const endIcon = L.divIcon({
            html: `<div style="background:#2E9E3F;color:#fff;font-weight:800;font-size:11px;width:28px;height:28px;border-radius:999px;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;box-shadow:0 3px 10px rgba(46,158,63,0.4)">B</div>`,
            className: '', iconSize: [28, 28], iconAnchor: [14, 14]
        });

        startMarker = L.marker([startCoords.lat, startCoords.lng], { icon: startIcon }).addTo(map).bindTooltip('Titik Awal (A)');
        endMarker = L.marker([endCoords.lat, endCoords.lng], { icon: endIcon }).addTo(map).bindTooltip('Titik Tujuan (B)');
    }

    function evaluateRoute(routeObj, isDetourClean = false) {
        const coordsLatLng = routeObj.coords;
        const step = Math.max(1, Math.floor(coordsLatLng.length / 40));
        let pmSum = 0;
        let samples = 0;
        const hitHotspots = new Map();

        for (let i = 0; i < coordsLatLng.length; i += step) {
            const [lat, lng] = coordsLatLng[i];
            const reading = localPm25At(lat, lng, isDetourClean);
            pmSum += reading.pm25;
            samples++;
            if (reading.nearHotspot) {
                hitHotspots.set(reading.nearHotspot.id, reading.nearHotspot);
            }
        }

        const profile = PROFILES[activeProfileKey];
        const avgPm25 = samples > 0 ? pmSum / samples : 18.0;
        const distanceKm = routeObj.distance / 1000;
        const durationSeconds = routeObj.duration || (distanceKm / profile.speedKmh) * 3600;
        const durationHours = Math.max(0.04, durationSeconds / 3600);
        const inhaledUg = avgPm25 * profile.breathingRate * durationHours;

        return {
            coords: coordsLatLng,
            steps: routeObj.steps || [],
            distanceKm: distanceKm,
            durationMin: Math.max(2, Math.round(durationSeconds / 60)),
            avgPm25: Number(avgPm25.toFixed(1)),
            inhaledUg: Number(inhaledUg.toFixed(1)),
            hitHotspots: Array.from(hitHotspots.values()),
            isClean: isDetourClean
        };
    }

    async function fetchOsrmRoutes(waypoints) {
        const profile = PROFILES[activeProfileKey].osrmProfile;
        const coordStr = waypoints.map(p => `${p.lng.toFixed(5)},${p.lat.toFixed(5)}`).join(';');
        const url = `https://router.project-osrm.org/route/v1/${profile}/${coordStr}?alternatives=true&steps=true&overview=full&geometries=geojson`;
        const ctrl = new AbortController();
        const timer = setTimeout(() => ctrl.abort(), 5500);
        try {
            const res = await fetch(url, { signal: ctrl.signal });
            clearTimeout(timer);
            if (!res.ok) return [];
            const data = await res.json();
            return (data.routes || []).map(r => {
                const rawSteps = (r.legs || []).flatMap(leg => leg.steps || []);
                const parsedSteps = rawSteps.map(s => {
                    const m = translateManeuver(s);
                    const loc = s.maneuver?.location || [waypoints[0].lng, waypoints[0].lat];
                    return {
                        icon: m.icon,
                        text: m.text,
                        distanceM: Math.round(s.distance || 0),
                        lat: loc[1],
                        lng: loc[0]
                    };
                });

                return {
                    coords: r.geometry.coordinates.map(([lng, lat]) => [lat, lng]),
                    distance: r.distance,
                    duration: r.duration,
                    steps: parsedSteps
                };
            });
        } catch {
            clearTimeout(timer);
            return [];
        }
    }

    function buildCleanWaypoint(start, end) {
        const midLat = (start.lat + end.lat) / 2;
        const midLng = (start.lng + end.lng) / 2;
        const dLat = end.lat - start.lat;
        const dLng = end.lng - start.lng;
        const len = Math.sqrt(dLat * dLat + dLng * dLng) || 0.01;

        const detourFactor = PROFILES[activeProfileKey].detourFactor;
        const offset = Math.min(0.035, Math.max(0.008, len * detourFactor));
        const candA = { lat: midLat + (-dLng / len) * offset, lng: midLng + (dLat / len) * offset };
        const candB = { lat: midLat - (-dLng / len) * offset, lng: midLng - (dLat / len) * offset };

        const minDistHotspot = (pt) => {
            if (!HOTSPOTS.length) return 99;
            return Math.min(...HOTSPOTS.map(h => haversineKm(pt.lat, pt.lng, h.lat, h.lng)));
        };

        const best = minDistHotspot(candA) >= minDistHotspot(candB) ? candA : candB;
        return {
            lat: Math.min(BOUNDS.north - 0.02, Math.max(BOUNDS.south + 0.02, best.lat)),
            lng: Math.min(BOUNDS.east - 0.02, Math.max(BOUNDS.west + 0.02, best.lng))
        };
    }

    function buildInterpolatedFallback(points, totalMeters) {
        const dense = [];
        for (let i = 0; i < points.length - 1; i++) {
            const [lat1, lng1] = points[i];
            const [lat2, lng2] = points[i + 1];
            for (let t = 0; t < 25; t++) {
                const f = t / 25;
                dense.push([lat1 + (lat2 - lat1) * f, lng1 + (lng2 - lng1) * f]);
            }
        }
        dense.push(points[points.length - 1]);
        const halfM = Math.round(totalMeters / 2);
        return {
            coords: dense,
            distance: totalMeters,
            duration: (totalMeters / 1000 / PROFILES[activeProfileKey].speedKmh) * 3600,
            steps: [
                { icon: '🚀', text: 'Mulai perjalanan mengikuti jalur rendah polusi', distanceM: halfM, lat: points[0][0], lng: points[0][1] },
                { icon: '⬆', text: 'Lanjutkan menyusuri koridor udara lebih bersih', distanceM: halfM, lat: points[1][0], lng: points[1][1] },
                { icon: '🏁', text: 'Anda telah tiba di tujuan!', distanceM: 0, lat: points[points.length - 1][0], lng: points[points.length - 1][1] }
            ]
        };
    }

    function renderTurnSteps(route, activeStepIndex = -1) {
        const listEl = document.getElementById('turnStepsList');
        const badgeEl = document.getElementById('stepsCountBadge');
        const steps = route?.steps || [];
        badgeEl.textContent = `${steps.length} langkah`;

        if (!steps.length) {
            listEl.innerHTML = '<li class="py-2 text-slate-400">Belum ada langkah belokan.</li>';
            return;
        }

        listEl.innerHTML = steps.map((s, idx) => {
            const activeCls = idx === activeStepIndex
                ? 'bg-emerald-50 border-l-4 border-[#2E9E3F] pl-2.5 font-bold text-[#0B3566]'
                : 'text-slate-600';
            const distText = s.distanceM >= 1000
                ? `${(s.distanceM / 1000).toFixed(1)} km`
                : (s.distanceM > 0 ? `${s.distanceM} m` : 'Tiba');
            return `
                <li class="py-2 px-2 rounded-lg flex items-center justify-between gap-2 ${activeCls}">
                    <span class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-sm shrink-0">${escapeHtml(s.icon)}</span>
                        <span>${escapeHtml(s.text)}</span>
                    </span>
                    <span class="text-[11px] font-mono text-slate-500 shrink-0">${escapeHtml(distText)}</span>
                </li>
            `;
        }).join('');
    }

    window.selectActiveRoute = function(choice) {
        activeRouteChoice = choice;
        const cleanCard = document.getElementById('cardCleanRoute');
        const fastCard = document.getElementById('cardFastRoute');
        const cleanTitle = document.getElementById('cleanRouteTitle');
        const fastTitle = document.getElementById('fastRouteTitle');

        if (choice === 'clean') {
            cleanCard.className = 'cursor-pointer rounded-2xl bg-white border-2 border-[#2E9E3F] p-4 shadow-sm space-y-2.5 transition';
            fastCard.className = 'cursor-pointer rounded-2xl bg-white border border-slate-200 hover:border-[#38A8E8] p-4 shadow-sm space-y-2.5 transition';
            if (cleanTitle) cleanTitle.textContent = 'RUTE TERBERSIH (DIPILIH)';
            if (fastTitle) fastTitle.textContent = 'RUTE TERCEPAT (STANDAR)';
        } else {
            cleanCard.className = 'cursor-pointer rounded-2xl bg-white border border-slate-200 hover:border-[#2E9E3F] p-4 shadow-sm space-y-2.5 transition';
            fastCard.className = 'cursor-pointer rounded-2xl bg-white border-2 border-[#38A8E8] p-4 shadow-sm space-y-2.5 transition';
            if (cleanTitle) cleanTitle.textContent = 'RUTE TERBERSIH (REKOMENDASI)';
            if (fastTitle) fastTitle.textContent = 'RUTE TERCEPAT (DIPILIH)';
        }

        if (evaluatedRoutes[choice]) {
            renderTurnSteps(evaluatedRoutes[choice]);
        }
    };

    async function calculateRoutes() {
        stopNavigation(false);
        const statusEl = document.getElementById('routingStatus');
        statusEl.textContent = 'Menghitung rute OSRM + langkah belokan + PM2.5...';
        updateEndpointMarkers();

        let primaryRoutes = await fetchOsrmRoutes([startCoords, endCoords]);
        let fastestRaw = null;
        let cleanestRaw = null;

        if (primaryRoutes.length >= 2) {
            // Compare OSRM alternatives neutrally by hotspot hits & PM2.5 exposure so the genuinely cleaner route is picked
            const scored = primaryRoutes.map((r, idx) => ({
                raw: r,
                idx,
                neutral: evaluateRoute(r, false),
            }));
            fastestRaw = primaryRoutes[0];

            const sortedByClean = [...scored].sort((a, b) => {
                if (a.neutral.hitHotspots.length !== b.neutral.hitHotspots.length) {
                    return a.neutral.hitHotspots.length - b.neutral.hitHotspots.length;
                }
                return a.neutral.inhaledUg - b.neutral.inhaledUg;
            });

            cleanestRaw = sortedByClean[0].raw;
            if (cleanestRaw === fastestRaw) {
                // If fastest route is already cleaner than alternative 1, try a clean detour waypoint avoiding hotspots
                const cleanWp = buildCleanWaypoint(startCoords, endCoords);
                const detourRoutes = await fetchOsrmRoutes([startCoords, cleanWp, endCoords]);
                if (detourRoutes.length > 0) {
                    const detourNeutral = evaluateRoute(detourRoutes[0], false);
                    const altNeutral = scored[1].neutral;
                    cleanestRaw = detourNeutral.hitHotspots.length <= altNeutral.hitHotspots.length
                        ? detourRoutes[0]
                        : scored[1].raw;
                } else {
                    cleanestRaw = scored[1].raw;
                }
            }
        } else if (primaryRoutes.length === 1) {
            fastestRaw = primaryRoutes[0];
            const cleanWp = buildCleanWaypoint(startCoords, endCoords);
            const detourRoutes = await fetchOsrmRoutes([startCoords, cleanWp, endCoords]);
            if (detourRoutes.length > 0) {
                cleanestRaw = detourRoutes[0];
            }
        }

        if (!fastestRaw) {
            const directM = Math.max(1200, haversineKm(startCoords.lat, startCoords.lng, endCoords.lat, endCoords.lng) * 1220);
            const midDirect = [(startCoords.lat + endCoords.lat) / 2, (startCoords.lng + endCoords.lng) / 2];
            fastestRaw = buildInterpolatedFallback([[startCoords.lat, startCoords.lng], midDirect, [endCoords.lat, endCoords.lng]], directM);
        }

        if (!cleanestRaw) {
            const wp = buildCleanWaypoint(startCoords, endCoords);
            cleanestRaw = buildInterpolatedFallback([[startCoords.lat, startCoords.lng], [wp.lat, wp.lng], [endCoords.lat, endCoords.lng]], fastestRaw.distance * 1.12);
        }

        const evalFast = evaluateRoute(fastestRaw, false);
        const evalClean = evaluateRoute(cleanestRaw, true);
        evaluatedRoutes = { clean: evalClean, fast: evalFast };

        if (fastestLine) map.removeLayer(fastestLine);
        if (cleanestLine) map.removeLayer(cleanestLine);

        fastestLine = L.polyline(evalFast.coords, {
            color: '#38A8E8',
            weight: 5,
            opacity: 0.85,
            dashArray: '8, 8'
        }).addTo(map).bindTooltip(`Rute Tercepat • ${evalFast.distanceKm.toFixed(1)} km • PM2.5 ~${evalFast.avgPm25} µg/m³`);

        cleanestLine = L.polyline(evalClean.coords, {
            color: '#2E9E3F',
            weight: 6,
            opacity: 0.95
        }).addTo(map).bindTooltip(`Rute Terbersih • ${evalClean.distanceKm.toFixed(1)} km • PM2.5 ~${evalClean.avgPm25} µg/m³`);

        const group = L.featureGroup([fastestLine, cleanestLine, startMarker, endMarker]);
        map.fitBounds(group.getBounds().pad(0.18));

        // Update comparison cards
        document.getElementById('cleanDistance').textContent = `${evalClean.distanceKm.toFixed(1)} km`;
        document.getElementById('cleanDuration').textContent = `${evalClean.durationMin} mnt`;
        document.getElementById('cleanExposure').textContent = `${evalClean.inhaledUg} µg`;
        document.getElementById('cleanNote').textContent = `Rata-rata PM2.5: ${evalClean.avgPm25} µg/m³ • Profil ${PROFILES[activeProfileKey].label}.`;

        document.getElementById('fastDistance').textContent = `${evalFast.distanceKm.toFixed(1)} km`;
        document.getElementById('fastDuration').textContent = `${evalFast.durationMin} mnt`;
        document.getElementById('fastExposure').textContent = `${evalFast.inhaledUg} µg`;
        document.getElementById('fastNote').textContent = `Rata-rata PM2.5: ${evalFast.avgPm25} µg/m³ • Jalur arteri utama.`;

        const savingsBadge = document.getElementById('cleanSavingsBadge');
        if (evalFast.inhaledUg > 0 && evalClean.inhaledUg < evalFast.inhaledUg) {
            const reduction = Math.round(((evalFast.inhaledUg - evalClean.inhaledUg) / evalFast.inhaledUg) * 100);
            savingsBadge.textContent = reduction > 0 ? `-${reduction}% Paparan PM2.5` : 'Lebih Bersih';
        } else if (evalClean.hitHotspots.length < evalFast.hitHotspots.length) {
            savingsBadge.textContent = 'Hindari Hotspot Asap';
        } else {
            savingsBadge.textContent = 'Paparan Setara';
        }

        // Automatic smoke hotspot warning (checks both fastest and cleanest routes)
        const warnBox = document.getElementById('smokeWarningBox');
        const warnText = document.getElementById('smokeWarningText');
        const fastBadge = document.getElementById('fastestHotspotBadge');

        if (evalFast.hitHotspots.length > 0 && evalClean.hitHotspots.length === 0) {
            const names = evalFast.hitHotspots.map(h => `${h.category} (${h.region})`).join(', ');
            warnText.textContent = `Rute tercepat melintasi dekat ${evalFast.hitHotspots.length} titik asap laporan warga aktif: ${names}. Disarankan navigasi menggunakan Rute Terbersih (garis hijau).`;
            warnBox.classList.remove('hidden');
            fastBadge.textContent = `⚠ Lewati ${evalFast.hitHotspots.length} Titik Asap`;
            fastBadge.className = 'px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[11px] font-bold';
        } else if (evalClean.hitHotspots.length > 0) {
            const allHits = new Map([...evalFast.hitHotspots, ...evalClean.hitHotspots].map(h => [h.id, h]));
            const names = Array.from(allHits.values()).map(h => `${h.category} (${h.region})`).join(', ');
            warnText.textContent = `Kedua rute melewati area dekat titik asap aktif (${names}). Sangat disarankan memakai masker KF94/N95 dan menutup kaca helm selama melintas.`;
            warnBox.classList.remove('hidden');
            fastBadge.textContent = evalFast.hitHotspots.length > 0
                ? `⚠ Lewati ${evalFast.hitHotspots.length} Titik Asap`
                : 'Bebas Hotspot';
            fastBadge.className = evalFast.hitHotspots.length > 0
                ? 'px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[11px] font-bold'
                : 'px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold';
        } else {
            warnBox.classList.add('hidden');
            fastBadge.textContent = 'Bebas Hotspot';
            fastBadge.className = 'px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold';
        }

        renderTurnSteps(evaluatedRoutes[activeRouteChoice]);
        statusEl.textContent = 'Siap navigasi real-time • OSRM Turn-by-Turn + PM2.5 Kediri Raya';
    }

    // ==================== REAL-TIME NAVIGATION ENGINE ====================

    function ensureUserMarker(lat, lng) {
        if (!userLiveMarker) {
            const liveIcon = L.divIcon({
                className: '',
                html: `<div class="nav-user-dot"><div class="nav-user-dot-core">▲</div></div>`,
                iconSize: [22, 22],
                iconAnchor: [11, 11]
            });
            userLiveMarker = L.marker([lat, lng], { icon: liveIcon, zIndexOffset: 1000 }).addTo(map);
        } else {
            userLiveMarker.setLatLng([lat, lng]);
        }
    }

    function updateNavigationTelemetry(lat, lng, route, coordIdx = null) {
        ensureUserMarker(lat, lng);

        // Find nearest point index along active route
        let nearestIdx = coordIdx;
        let minD = 999;
        if (nearestIdx === null) {
            route.coords.forEach(([rlat, rlng], idx) => {
                const d = haversineKm(lat, lng, rlat, rlng);
                if (d < minD) {
                    minD = d;
                    nearestIdx = idx;
                }
            });
        }

        // Draw traveled breadcrumb trail
        const traveledCoords = route.coords.slice(0, Math.max(1, nearestIdx + 1));
        if (traveledLine) map.removeLayer(traveledLine);
        traveledLine = L.polyline(traveledCoords, {
            color: '#0B3566',
            weight: 6,
            opacity: 0.9
        }).addTo(map);

        // Remaining distance along polyline
        let remainKm = 0;
        for (let i = nearestIdx; i < route.coords.length - 1; i++) {
            remainKm += haversineKm(route.coords[i][0], route.coords[i][1], route.coords[i + 1][0], route.coords[i + 1][1]);
        }

        const speedKmh = PROFILES[activeProfileKey].speedKmh;
        const remainMin = Math.max(1, Math.round((remainKm / speedKmh) * 60));

        // Live local PM2.5 at current moving position (distance-based exposure, independent of simulation speed)
        const reading = localPm25At(lat, lng, route.isClean);
        let deltaKm = 0;
        if (navMode === 'sim' && lastNavCoordIdx !== null && nearestIdx > lastNavCoordIdx) {
            for (let i = lastNavCoordIdx; i < nearestIdx; i++) {
                deltaKm += haversineKm(route.coords[i][0], route.coords[i][1], route.coords[i + 1][0], route.coords[i + 1][1]);
            }
        } else if (navMode === 'gps' && lastLivePos) {
            const movedKm = haversineKm(lastLivePos.lat, lastLivePos.lng, lat, lng);
            // Ignore GPS jitter > 0.5 km in a single update or tiny sub-3m drift
            if (movedKm >= 0.003 && movedKm <= 0.5) {
                deltaKm = movedKm;
            }
        }
        lastNavCoordIdx = nearestIdx;
        lastLivePos = { lat, lng };

        const deltaHours = deltaKm / Math.max(1, speedKmh);
        const stepExposureUg = reading.pm25 * PROFILES[activeProfileKey].breathingRate * deltaHours;
        accumulatedUg += stepExposureUg;

        document.getElementById('hudRemainDist').textContent = remainKm < 1 ? `${Math.round(remainKm * 1000)} m` : `${remainKm.toFixed(1)} km`;
        document.getElementById('hudRemainTime').textContent = remainKm <= 0.05 ? 'Tiba!' : `${remainMin} mnt`;

        const pmEl = document.getElementById('hudLivePm25');
        pmEl.textContent = `${reading.pm25} µg/m³`;
        pmEl.className = reading.pm25 <= 15
            ? 'text-sm font-extrabold text-[#6CE07D]'
            : (reading.pm25 <= 35 ? 'text-sm font-extrabold text-amber-300' : 'text-sm font-extrabold text-red-300');

        document.getElementById('hudAccumulatedUg').textContent = `${accumulatedUg.toFixed(1)} µg`;

        // Find active turn-by-turn step
        if (route.steps.length > 0) {
            let bestStepIdx = 0;
            let bestStepDist = 999;
            route.steps.forEach((s, idx) => {
                const d = haversineKm(lat, lng, s.lat, s.lng);
                if (d < bestStepDist) {
                    bestStepDist = d;
                    bestStepIdx = idx;
                }
            });

            // Show upcoming step once user passes current step
            const displayIdx = remainKm <= 0.06
                ? route.steps.length - 1
                : Math.min(route.steps.length - 1, bestStepIdx + (bestStepDist < 0.06 ? 1 : 0));

            const curStep = route.steps[displayIdx];
            const distToStepM = Math.round(haversineKm(lat, lng, curStep.lat, curStep.lng) * 1000);

            document.getElementById('hudManeuverIcon').textContent = curStep.icon;
            document.getElementById('hudInstruction').textContent = curStep.text;
            document.getElementById('hudNextStepDist').textContent =
                displayIdx === route.steps.length - 1
                    ? 'Tujuan di depan Anda'
                    : (distToStepM > 20 ? `Dalam ${distToStepM} meter` : 'Sekarang');

            if (displayIdx !== lastSpokenStepIdx) {
                lastSpokenStepIdx = displayIdx;
                renderTurnSteps(route, displayIdx);
                speakId(curStep.text);
            }
        }

        // Real-time smoke hotspot proximity alert
        const alertBox = document.getElementById('hudSmokeAlert');
        const alertText = document.getElementById('hudSmokeAlertText');
        if (reading.nearHotspot) {
            const h = reading.nearHotspot;
            alertText.textContent = `Peringatan Dini: Mendekati titik ${h.category} (${h.distM} m). Tutup kaca helm / gunakan masker KF94!`;
            alertBox.classList.remove('hidden');
            if (lastHotspotAlertId !== h.id) {
                lastHotspotAlertId = h.id;
                speakId(`Perhatian, Anda mendekati titik ${h.category}. Harap gunakan masker.`);
            }
        } else {
            alertBox.classList.add('hidden');
        }

        if (autoFollow) {
            map.panTo([lat, lng], { animate: true, duration: 0.4 });
        }

        // Auto-reroute in GPS mode if user goes >180m off route inside Kediri Raya (with 15s cooldown)
        const nowMs = Date.now();
        if (navMode === 'gps' && minD > 0.18 && maxBounds.contains([lat, lng]) && !isRerouting && (nowMs - lastRerouteAt) > 15000) {
            isRerouting = true;
            lastRerouteAt = nowMs;
            startCoords = { lat, lng };
            speakId('Anda keluar jalur, menghitung ulang rute udara terbersih.');
            calculateRoutes()
                .then(() => startGpsNavigation(true))
                .finally(() => { isRerouting = false; });
        }
    }

    function startSimNavigation() {
        const route = evaluatedRoutes[activeRouteChoice];
        if (!route || !route.coords.length) return;

        stopNavigation(false);
        navMode = 'sim';
        accumulatedUg = 0;
        lastNavCoordIdx = 0;
        lastLivePos = null;
        lastSpokenStepIdx = -1;
        lastHotspotAlertId = null;
        autoFollow = true;

        document.getElementById('liveNavHud').classList.remove('hidden');
        document.getElementById('navActiveControls').classList.remove('hidden');
        document.getElementById('simSpeedControls').classList.remove('hidden');
        document.getElementById('hudModeBadge').textContent = 'SIMULASI NAVIGASI REAL-TIME';
        document.getElementById('routingStatus').textContent = 'Navigasi simulasi aktif • Memantau PM2.5 per ruas jalan';

        let idx = 0;
        map.setView(route.coords[0], 15, { animate: true });
        updateNavigationTelemetry(route.coords[0][0], route.coords[0][1], route, 0);

        const stepInterval = () => {
            if (navMode !== 'sim') return;
            idx += Math.max(1, Math.round(simSpeed * Math.max(1, route.coords.length / 110)));
            if (idx >= route.coords.length - 1) {
                const last = route.coords[route.coords.length - 1];
                updateNavigationTelemetry(last[0], last[1], route, route.coords.length - 1);
                document.getElementById('routingStatus').textContent = 'Simulasi selesai — Anda telah tiba di tujuan dengan aman!';
                navMode = null;
                clearTimeout(simTimerId);
                return;
            }
            const [lat, lng] = route.coords[idx];
            updateNavigationTelemetry(lat, lng, route, idx);
            simTimerId = setTimeout(stepInterval, Math.max(180, Math.round(520 / simSpeed)));
        };

        simTimerId = setTimeout(stepInterval, 500);
    }

    function startGpsNavigation(preserveAccumulated = false) {
        const route = evaluatedRoutes[activeRouteChoice];
        if (!route || !route.coords.length) return;
        if (!navigator.geolocation) {
            alert('Browser Anda tidak mendukung GPS. Gunakan mode "Simulasi Jalan" untuk melihat navigasi real-time.');
            return;
        }

        stopNavigation(false);
        navMode = 'gps';
        if (!preserveAccumulated) {
            accumulatedUg = 0;
        }
        lastNavCoordIdx = null;
        lastLivePos = null;
        lastSpokenStepIdx = -1;
        lastHotspotAlertId = null;
        autoFollow = true;

        document.getElementById('liveNavHud').classList.remove('hidden');
        document.getElementById('navActiveControls').classList.remove('hidden');
        document.getElementById('simSpeedControls').classList.add('hidden');
        document.getElementById('hudModeBadge').textContent = 'GPS LIVE AKTIF';
        document.getElementById('routingStatus').textContent = 'Melacak posisi GPS Anda secara real-time...';
        if (!preserveAccumulated) {
            speakId('Navigasi real-time NapasAman dimulai. Mengikuti rute udara terbersih.');
        }

        gpsWatchId = navigator.geolocation.watchPosition(
            (pos) => {
                const { latitude, longitude } = pos.coords;
                if (!maxBounds.contains([latitude, longitude])) {
                    document.getElementById('hudInstruction').textContent =
                        'Posisi GPS di luar Eks-Karesidenan Kediri — gunakan mode "Simulasi Jalan" untuk demo.';
                    return;
                }
                updateNavigationTelemetry(latitude, longitude, route, null);
            },
            () => {
                document.getElementById('routingStatus').textContent = 'Izin GPS ditolak. Coba tombol "Simulasi Jalan" untuk demo!';
            },
            { enableHighAccuracy: true, maximumAge: 2000, timeout: 10000 }
        );
    }

    function stopNavigation(resetUi = true) {
        navMode = null;
        if (simTimerId) {
            clearTimeout(simTimerId);
            simTimerId = null;
        }
        if (gpsWatchId !== null && navigator.geolocation) {
            navigator.geolocation.clearWatch(gpsWatchId);
            gpsWatchId = null;
        }
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
        if (resetUi) {
            document.getElementById('liveNavHud').classList.add('hidden');
            document.getElementById('navActiveControls').classList.add('hidden');
            if (traveledLine) { map.removeLayer(traveledLine); traveledLine = null; }
            if (userLiveMarker) { map.removeLayer(userLiveMarker); userLiveMarker = null; }
            document.getElementById('routingStatus').textContent = 'Navigasi dihentikan.';
        }
    }

    // ==================== UI EVENT LISTENERS ====================

    // Profile buttons
    document.querySelectorAll('.profile-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            activeProfileKey = btn.dataset.profile;
            document.querySelectorAll('.profile-btn').forEach(b => {
                b.className = 'profile-btn px-2 py-2 rounded-lg text-[11px] font-bold text-slate-600 hover:text-[#0B3566] transition text-center leading-tight';
            });
            btn.className = 'profile-btn px-2 py-2 rounded-lg text-[11px] font-bold bg-[#0B3566] text-white shadow transition text-center leading-tight';
            document.getElementById('profileDesc').textContent = PROFILES[activeProfileKey].desc;
            calculateRoutes();
        });
    });

    document.getElementById('startPreset').addEventListener('change', (e) => {
        startCoords = parseCoord(e.target.value);
        calculateRoutes();
    });
    document.getElementById('endPreset').addEventListener('change', (e) => {
        endCoords = parseCoord(e.target.value);
        calculateRoutes();
    });
    document.getElementById('btnCalculateRoute').addEventListener('click', calculateRoutes);
    document.getElementById('btnStartSimNav').addEventListener('click', startSimNavigation);
    document.getElementById('btnStartGpsNav').addEventListener('click', () => startGpsNavigation(false));
    document.getElementById('btnStopNav').addEventListener('click', () => stopNavigation(true));

    document.getElementById('btnRecenterMap').addEventListener('click', () => {
        autoFollow = true;
        if (userLiveMarker) {
            map.setView(userLiveMarker.getLatLng(), 15, { animate: true });
        }
    });

    map.on('dragstart', () => { autoFollow = false; });

    document.getElementById('btnToggleVoice').addEventListener('click', (e) => {
        voiceEnabled = !voiceEnabled;
        e.currentTarget.textContent = voiceEnabled ? '🔊 Suara ON' : '🔇 Suara OFF';
        if (!voiceEnabled && 'speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
    });

    document.querySelectorAll('.sim-speed-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            simSpeed = Number(btn.dataset.speed) || 1;
            document.querySelectorAll('.sim-speed-btn').forEach(b => {
                b.className = 'sim-speed-btn px-2 py-1 rounded-lg bg-white/15 text-white font-bold';
            });
            btn.className = 'sim-speed-btn px-2 py-1 rounded-lg bg-white text-[#0B3566] font-extrabold';
        });
    });

    document.getElementById('btnUseMyGpsStart').addEventListener('click', () => {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung GPS.');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const { latitude, longitude } = pos.coords;
                if (!maxBounds.contains([latitude, longitude])) {
                    alert('Lokasi GPS Anda saat ini berada di luar wilayah Eks-Karesidenan Kediri. Silakan pilih preset atau klik peta.');
                    return;
                }
                startCoords = { lat: latitude, lng: longitude };
                calculateRoutes();
            },
            () => alert('Izin GPS ditolak atau tidak tersedia.')
        );
    });

    const hintEl = document.getElementById('pickModeHint');
    document.getElementById('btnPickStart').addEventListener('click', () => {
        pickMode = 'start';
        hintEl.textContent = 'Mode aktif: Klik lokasi di peta Eks-Karesidenan Kediri untuk menentukan Titik Awal (A).';
        hintEl.classList.remove('hidden');
    });
    document.getElementById('btnPickEnd').addEventListener('click', () => {
        pickMode = 'end';
        hintEl.textContent = 'Mode aktif: Klik lokasi di peta Eks-Karesidenan Kediri untuk menentukan Titik Tujuan (B).';
        hintEl.classList.remove('hidden');
    });

    map.on('click', (e) => {
        if (!pickMode) return;
        const { lat, lng } = e.latlng;
        if (!maxBounds.contains(e.latlng)) {
            alert('Lokasi harus berada di dalam wilayah Eks-Karesidenan Kediri.');
            return;
        }
        if (pickMode === 'start') {
            startCoords = { lat, lng };
        } else {
            endCoords = { lat, lng };
        }
        pickMode = null;
        hintEl.classList.add('hidden');
        calculateRoutes();
    });

    // Run initial route calculation on page load
    calculateRoutes();
</script>
@endpush
