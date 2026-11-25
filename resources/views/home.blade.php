@extends('layouts.app')

@section('title', 'NapasAman — Cek Kualitas Udara Eks-Karesidenan Kediri')

@section('content')
<div x-data="homeDashboard()" x-init="init()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-8">

    <!-- Hero & Active Region Spotlight -->
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0B3566] via-[#0F437E] to-[#165A9E] text-white p-6 sm:p-8 lg:p-10 shadow-xl">
        <div class="absolute -right-16 -top-16 w-72 h-72 rounded-full bg-[#38A8E8]/20 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 rounded-full bg-[#2E9E3F]/25 blur-3xl pointer-events-none"></div>

        <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur text-xs font-semibold text-sky-100">
                    <span class="w-2 h-2 rounded-full bg-[#2E9E3F]"></span>
                    Pantauan Kualitas Udara Real-Time • Eks-Karesidenan Kediri
                </div>

                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                    Cek Kualitas Udara.<br>
                    <span class="text-[#6CE07D]">Hidup Lebih Sehat.</span>
                </h1>

                <p class="text-sm sm:text-base text-sky-100/90 max-w-2xl leading-relaxed">
                    Pantau indeks AQI & PM2.5 di 7 daerah Eks-Karesidenan Kediri, temukan jalur perjalanan paling bersih, hitung risiko paparan harian, dan laporkan titik pembakaran sampah secara real-time.
                </p>

                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="{{ route('rute-bersih') }}"
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-[#2E9E3F] hover:bg-[#258634] text-white text-sm font-bold shadow-lg shadow-emerald-900/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Cari Rute Bersih
                    </a>
                    <a href="{{ route('lapor-asap.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white/15 hover:bg-white/25 text-white text-sm font-bold backdrop-blur transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Lapor Titik Asap ({{ $activeReportsCount }})
                    </a>
                    <a href="{{ route('bot-wa.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-3 rounded-2xl bg-[#38A8E8]/25 hover:bg-[#38A8E8]/35 text-white text-sm font-bold backdrop-blur transition">
                        💬 Coba Bot WA
                    </a>
                </div>
            </div>

            <!-- Selected Region Live Summary Card -->
            <div class="lg:col-span-5">
                <div class="rounded-2xl bg-white text-slate-900 p-5 sm:p-6 shadow-2xl space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-[#38A8E8]">Wilayah Terpilih</p>
                            <h2 class="text-xl sm:text-2xl font-extrabold text-[#0B3566]" x-text="current.name"></h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Diperbarui: <span x-text="current.recorded_at"></span>
                                • <span class="font-semibold" x-text="current.source === 'open-meteo' ? 'Open-Meteo Live' : 'Data Cache MySQL'"></span>
                            </p>
                        </div>
                        <a href="{{ route('home', ['refresh' => 1]) }}"
                           title="Segarkan data dari Open-Meteo"
                           class="p-2 rounded-xl bg-slate-100 hover:bg-[#38A8E8]/15 text-[#0B3566] transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </a>
                    </div>

                    <div class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <div>
                            <span class="text-xs font-semibold text-slate-500">Indeks US AQI / ISPU</span>
                            <div class="flex items-baseline gap-2 mt-0.5">
                                <span class="text-4xl sm:text-5xl font-extrabold text-[#0B3566]" x-text="current.aqi"></span>
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                                      :class="current.meta.badge_class"
                                      x-text="current.category"></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-slate-500">PM2.5</span>
                            <p class="text-2xl font-extrabold text-[#0B3566]">
                                <span x-text="current.pm25"></span>
                                <span class="text-xs font-semibold text-slate-500">µg/m³</span>
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed" x-text="current.meta.summary"></p>

                    <div class="grid grid-cols-3 gap-2 pt-1">
                        <div class="rounded-xl bg-sky-50/70 border border-sky-100 p-2.5 text-center">
                            <p class="text-[11px] font-semibold text-slate-500">PM10</p>
                            <p class="text-sm font-extrabold text-[#0B3566]"><span x-text="current.pm10"></span> <span class="text-[10px] font-medium">µg/m³</span></p>
                        </div>
                        <div class="rounded-xl bg-sky-50/70 border border-sky-100 p-2.5 text-center">
                            <p class="text-[11px] font-semibold text-slate-500">NO₂</p>
                            <p class="text-sm font-extrabold text-[#0B3566]"><span x-text="current.no2"></span> <span class="text-[10px] font-medium">µg/m³</span></p>
                        </div>
                        <div class="rounded-xl bg-emerald-50/80 border border-emerald-100 p-2.5 text-center">
                            <p class="text-[11px] font-semibold text-slate-500">Jam Lari Aman</p>
                            <p class="text-xs font-extrabold text-[#2E9E3F] mt-0.5" x-text="current.jogging.primary.hour.replace(' WIB', '')"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7 Regions Grid (Eks-Karesidenan Kediri) -->
    <section class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2">
            <div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-[#0B3566]">Kualitas Udara 7 Wilayah Eks-Karesidenan Kediri</h2>
                <p class="text-sm text-slate-500">Klik salah satu wilayah di bawah untuk melihat tren PM2.5 per jam & rekomendasi aktivitas.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-2 h-2 rounded-full bg-[#2E9E3F]"></span>0–50 Baik</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200"><span class="w-2 h-2 rounded-full bg-amber-500"></span>51–100 Sedang</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-orange-50 text-orange-700 border border-orange-200"><span class="w-2 h-2 rounded-full bg-orange-500"></span>101–150 Sensitif</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 text-red-700 border border-red-200"><span class="w-2 h-2 rounded-full bg-red-600"></span>&gt;150 Tidak Sehat</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($regions as $region)
                @php
                    $aq = $region->latestAirQuality;
                    $meta = $aq->meta;
                @endphp
                <button type="button"
                        @click="selectRegion('{{ $region->slug }}')"
                        :class="selectedSlug === '{{ $region->slug }}' ? 'ring-2 ring-[#38A8E8] border-[#38A8E8] bg-sky-50/30 shadow-md' : 'border-slate-200 bg-white hover:border-[#38A8E8]/60 hover:shadow-md'"
                        class="text-left rounded-2xl border p-4 transition-all duration-150 flex flex-col justify-between gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-[#0B3566] text-base leading-snug">{{ $region->name }}</h3>
                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">{{ number_format($region->latitude, 4) }}, {{ number_format($region->longitude, 4) }}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0 {{ $meta['badge_class'] }}">
                            {{ $meta['short_label'] }}
                        </span>
                    </div>

                    <div class="flex items-end justify-between pt-1">
                        <div>
                            <span class="text-[11px] text-slate-500 font-semibold">US AQI</span>
                            <p class="text-3xl font-extrabold text-[#0B3566] leading-none mt-0.5">{{ $aq->aqi }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] text-slate-500 font-semibold">PM2.5</span>
                            <p class="text-base font-bold text-slate-800">{{ number_format($aq->pm25, 1) }} <span class="text-xs font-normal text-slate-500">µg/m³</span></p>
                        </div>
                    </div>
                </button>
            @endforeach

            <!-- Quick Summary KPI Card (8th slot to balance 4-col grid cleanly) -->
            <div class="rounded-2xl bg-gradient-to-br from-[#2E9E3F] to-[#218030] text-white p-4 flex flex-col justify-between shadow-sm">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-100">Ringkasan Kediri Raya</p>
                    <p class="text-2xl font-extrabold mt-1">Rata-rata AQI {{ $avgAqi }}</p>
                    <p class="text-xs text-emerald-50/90 mt-1">
                        {{ $activeReportsCount }} titik asap aktif sedang dipantau • {{ $totalWaInteractions }} interaksi WA Bot Sobat Napas.
                    </p>
                </div>
                <a href="{{ route('kalkulator-paparan') }}"
                   class="mt-3 inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl bg-white text-[#0B3566] hover:bg-emerald-50 text-xs font-bold transition">
                    Hitung Paparan Pribadi →
                </a>
            </div>
        </div>
    </section>

    <!-- Recommendations by 3 User Segments + Hourly PM2.5 Trend Chart -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- 3-Segment Activity Recommendation Filter -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm flex flex-col justify-between space-y-5">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-[#2E9E3F]">Rekomendasi Aktivitas</span>
                        <h2 class="text-lg sm:text-xl font-extrabold text-[#0B3566]">Panduan Sesuai Kebutuhan Anda</h2>
                    </div>
                    <span class="px-2.5 py-1 rounded-xl bg-slate-100 text-[#0B3566] text-xs font-bold" x-text="current.name"></span>
                </div>

                <!-- Segment Tabs -->
                <div class="grid grid-cols-3 gap-2 p-1.5 rounded-2xl bg-slate-100">
                    <button type="button" @click="segment = 'komuter'"
                            :class="segment === 'komuter' ? 'bg-[#0B3566] text-white shadow' : 'text-slate-600 hover:text-[#0B3566]'"
                            class="px-2.5 py-2.5 rounded-xl text-xs font-bold transition text-center leading-tight">
                        🛵 Komuter &amp; Mahasiswa
                    </button>
                    <button type="button" @click="segment = 'keluarga'"
                            :class="segment === 'keluarga' ? 'bg-[#0B3566] text-white shadow' : 'text-slate-600 hover:text-[#0B3566]'"
                            class="px-2.5 py-2.5 rounded-xl text-xs font-bold transition text-center leading-tight">
                        👨‍👩‍👧 Keluarga &amp; Lansia
                    </button>
                    <button type="button" @click="segment = 'pelari'"
                            :class="segment === 'pelari' ? 'bg-[#2E9E3F] text-white shadow' : 'text-slate-600 hover:text-[#0B3566]'"
                            class="px-2.5 py-2.5 rounded-xl text-xs font-bold transition text-center leading-tight">
                        🏃 Pelari / Jogging
                    </button>
                </div>

                <!-- Active Segment Detail -->
                <div class="rounded-2xl bg-slate-50 border border-slate-200/70 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-extrabold text-[#0B3566] text-base" x-text="activeRecommendation.title"></h3>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold"
                              :class="activeRecommendation.badge"
                              x-text="activeRecommendation.status"></span>
                    </div>

                    <p class="text-sm font-semibold text-slate-800 leading-snug" x-text="activeRecommendation.headline"></p>

                    <!-- Extra Jogging Safe Hours Box when Pelari tab is active -->
                    <div x-show="segment === 'pelari'" x-transition class="grid grid-cols-2 gap-2 pt-1">
                        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3">
                            <p class="text-[11px] font-bold text-emerald-700">🌅 Terbaik Pagi Ini</p>
                            <p class="text-sm font-extrabold text-[#0B3566] mt-0.5" x-text="current.jogging.morning.hour"></p>
                            <p class="text-[11px] text-slate-600">PM2.5 ~<span x-text="current.jogging.morning.pm25"></span> µg/m³</p>
                        </div>
                        <div class="rounded-xl bg-sky-50 border border-sky-200 p-3">
                            <p class="text-[11px] font-bold text-[#0B3566]">🌇 Alternatif Sore</p>
                            <p class="text-sm font-extrabold text-[#0B3566] mt-0.5" x-text="current.jogging.afternoon.hour"></p>
                            <p class="text-[11px] text-slate-600">PM2.5 ~<span x-text="current.jogging.afternoon.pm25"></span> µg/m³</p>
                        </div>
                    </div>

                    <ul class="space-y-2 pt-1">
                        <template x-for="(tip, idx) in activeRecommendation.tips" :key="idx">
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                <span class="mt-0.5 w-5 h-5 rounded-full bg-[#2E9E3F]/15 text-[#2E9E3F] flex items-center justify-center shrink-0 text-xs font-bold">✓</span>
                                <span x-text="tip"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100">
                <a :href="'{{ route('kalkulator-paparan') }}?region=' + current.slug"
                   class="flex-1 text-center px-4 py-2.5 rounded-xl bg-[#0B3566] hover:bg-[#08274C] text-white text-xs font-bold transition">
                    Simulasi Paparan di Wilayah Ini
                </a>
                <a href="{{ route('rute-bersih') }}"
                   class="flex-1 text-center px-4 py-2.5 rounded-xl bg-[#2E9E3F] hover:bg-[#237E31] text-white text-xs font-bold transition">
                    Buka Peta Rute Bersih
                </a>
            </div>
        </div>

        <!-- Hourly PM2.5 Trend Chart (Chart.js) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm flex flex-col justify-between space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#38A8E8]">Grafik 24 Jam</span>
                    <h2 class="text-lg sm:text-xl font-extrabold text-[#0B3566]">
                        Tren PM2.5 per Jam — <span x-text="current.name"></span>
                    </h2>
                </div>
                <div class="inline-flex items-center gap-2 text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#38A8E8]"></span>
                    Batas Aman Harian WHO: <strong class="text-[#0B3566]">15 µg/m³</strong>
                </div>
            </div>

            <div class="relative h-72 sm:h-80 w-full">
                <canvas id="pm25HourlyChart"></canvas>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-100 text-xs">
                <div class="rounded-xl bg-emerald-50/70 px-3 py-2.5 border border-emerald-100">
                    <p class="text-slate-500 font-semibold">Jam Terbersih Hari Ini</p>
                    <p class="font-extrabold text-[#2E9E3F] text-sm mt-0.5" x-text="current.jogging.primary.hour"></p>
                </div>
                <div class="rounded-xl bg-sky-50/70 px-3 py-2.5 border border-sky-100">
                    <p class="text-slate-500 font-semibold">PM2.5 Terendah / Tertinggi</p>
                    <p class="font-extrabold text-[#0B3566] text-sm mt-0.5">
                        <span x-text="Math.min(...current.trend.pm25).toFixed(1)"></span> – <span x-text="Math.max(...current.trend.pm25).toFixed(1)"></span> µg/m³
                    </p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2.5 border border-slate-200">
                    <p class="text-slate-500 font-semibold">Sumber Data</p>
                    <p class="font-extrabold text-[#0B3566] text-sm mt-0.5" x-text="current.source === 'open-meteo' ? 'Open-Meteo Air Quality' : 'MySQL Local Cache'"></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Feature Cards Strip -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('rute-bersih') }}" class="group rounded-2xl bg-white border border-slate-200 p-5 hover:border-[#2E9E3F] hover:shadow-md transition">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-[#2E9E3F] flex items-center justify-center text-xl mb-3 group-hover:scale-105 transition">🗺️</div>
            <h3 class="font-extrabold text-[#0B3566] text-base">Rute Rendah Polusi</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Bandingkan rute tercepat vs rute terbersih di Kediri Raya sambil menghindari titik asap warga.</p>
        </a>

        <a href="{{ route('kalkulator-paparan') }}" class="group rounded-2xl bg-white border border-slate-200 p-5 hover:border-[#38A8E8] hover:shadow-md transition">
            <div class="w-11 h-11 rounded-2xl bg-sky-50 text-[#38A8E8] flex items-center justify-center text-xl mb-3 group-hover:scale-105 transition">🧮</div>
            <h3 class="font-extrabold text-[#0B3566] text-base">Kalkulator Paparan PM2.5</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Hitung total partikel PM2.5 terhirup saat naik motor, jalan kaki, atau jogging &amp; analogi batang rokok.</p>
        </a>

        <a href="{{ route('lapor-asap.index') }}" class="group rounded-2xl bg-white border border-slate-200 p-5 hover:border-orange-400 hover:shadow-md transition">
            <div class="w-11 h-11 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl mb-3 group-hover:scale-105 transition">🔥</div>
            <h3 class="font-extrabold text-[#0B3566] text-base">Lapor Titik Asap</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Laporkan pembakaran sampah terbuka, asap kendaraan, atau debu konstruksi dengan pin peta &amp; foto.</p>
        </a>

        <a href="{{ route('bot-wa.index') }}" class="group rounded-2xl bg-white border border-slate-200 p-5 hover:border-[#2E9E3F] hover:shadow-md transition">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-[#2E9E3F] flex items-center justify-center text-xl mb-3 group-hover:scale-105 transition">💬</div>
            <h3 class="font-extrabold text-[#0B3566] text-base">WA Bot "Sobat Napas"</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Cek AQI, jam jogging aman, dan kirim laporan titik asap cukup lewat chat WhatsApp tanpa instalasi rumit.</p>
        </a>
    </section>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const REGIONS_DATA = @json($regionsPayload);
    const INITIAL_SLUG = @json($selectedRegion->slug);

    let pm25ChartInstance = null;

    function homeDashboard() {
        return {
            regions: REGIONS_DATA,
            selectedSlug: INITIAL_SLUG,
            segment: 'komuter',

            get current() {
                return this.regions[this.selectedSlug] || Object.values(this.regions)[0];
            },

            get activeRecommendation() {
                return this.current.recommendations[this.segment] || this.current.recommendations.komuter;
            },

            init() {
                this.$nextTick(() => this.renderChart());
            },

            selectRegion(slug) {
                this.selectedSlug = slug;
                this.$nextTick(() => this.renderChart());
            },

            renderChart() {
                const canvas = document.getElementById('pm25HourlyChart');
                if (!canvas) return;

                const ctx = canvas.getContext('2d');
                const labels = this.current.trend.labels;
                const dataPm25 = this.current.trend.pm25;
                const whoThreshold = labels.map(() => 15);

                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(56, 168, 232, 0.35)');
                gradient.addColorStop(1, 'rgba(46, 158, 63, 0.02)');

                if (pm25ChartInstance) {
                    pm25ChartInstance.data.labels = labels;
                    pm25ChartInstance.data.datasets[0].data = dataPm25;
                    pm25ChartInstance.data.datasets[1].data = whoThreshold;
                    pm25ChartInstance.update();
                    return;
                }

                pm25ChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'PM2.5 (µg/m³)',
                                data: dataPm25,
                                borderColor: '#0B3566',
                                backgroundColor: gradient,
                                borderWidth: 3,
                                fill: true,
                                tension: 0.38,
                                pointRadius: 3,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#38A8E8',
                                pointBorderColor: '#FFFFFF',
                                pointBorderWidth: 2,
                            },
                            {
                                label: 'Batas Aman WHO (15 µg/m³)',
                                data: whoThreshold,
                                borderColor: '#2E9E3F',
                                borderWidth: 1.8,
                                borderDash: [6, 6],
                                fill: false,
                                pointRadius: 0,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    usePointStyle: true,
                                    font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' }
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0B3566',
                                titleFont: { family: 'Plus Jakarta Sans', weight: '700' },
                                bodyFont: { family: 'Plus Jakarta Sans' },
                                padding: 12,
                                cornerRadius: 12,
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    maxTicksLimit: 12,
                                    font: { family: 'Plus Jakarta Sans', size: 11 },
                                    color: '#64748B'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#F1F5F9' },
                                ticks: {
                                    font: { family: 'Plus Jakarta Sans', size: 11 },
                                    color: '#64748B',
                                    callback: (v) => v + ' µg/m³'
                                }
                            }
                        }
                    }
                });
            }
        }
    }
</script>
@endpush
