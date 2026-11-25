@extends('layouts.app')

@section('title', 'Kalkulator Paparan PM2.5 — NapasAman Kediri Raya')

@section('content')
<div x-data="exposureCalculator()" x-init="init()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <!-- Header -->
    <div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50 text-[#0B3566] text-xs font-bold border border-sky-200">
            🧮 Edukasi Risiko Kesehatan • Eks-Karesidenan Kediri
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0B3566] mt-2">Kalkulator &amp; Visualisasi Paparan PM2.5</h1>
        <p class="text-sm text-slate-600 mt-1 max-w-2xl">
            Hitung estimasi partikel halus PM2.5 yang masuk ke paru-paru Anda berdasarkan data kualitas udara real-time di Kediri Raya, jenis aktivitas, dan durasi.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Input Form Card -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-5">
            <h2 class="text-lg font-extrabold text-[#0B3566]">Parameter Aktivitas Anda</h2>

            <!-- Region Selector -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-[#0B3566]">1. Pilih Wilayah Eks-Karesidenan Kediri</label>
                <select x-model="regionSlug" @change="recalculate()"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-3 text-sm font-semibold text-[#0B3566] focus:border-[#38A8E8] focus:bg-white focus:outline-none">
                    @foreach ($regionsData as $reg)
                        <option value="{{ $reg['slug'] }}">
                            {{ $reg['name'] }} — PM2.5 {{ $reg['pm25'] }} µg/m³ (AQI {{ $reg['aqi'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Activity Selector -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-[#0B3566]">2. Pilih Jenis Aktivitas</label>
                <div class="grid grid-cols-2 gap-2.5">
                    <button type="button" @click="activity = 'motor'; recalculate()"
                            :class="activity === 'motor' ? 'border-[#0B3566] bg-[#0B3566] text-white shadow-md' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-[#38A8E8]'"
                            class="rounded-2xl border p-3.5 text-left transition space-y-1">
                        <span class="text-xl">🛵</span>
                        <p class="text-xs font-extrabold">Naik Motor</p>
                        <p class="text-[11px] opacity-80">Paparan jalan raya 1.65x</p>
                    </button>

                    <button type="button" @click="activity = 'jalan_kaki'; recalculate()"
                            :class="activity === 'jalan_kaki' ? 'border-[#0B3566] bg-[#0B3566] text-white shadow-md' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-[#38A8E8]'"
                            class="rounded-2xl border p-3.5 text-left transition space-y-1">
                        <span class="text-xl">🚶</span>
                        <p class="text-xs font-extrabold">Jalan Kaki</p>
                        <p class="text-[11px] opacity-80">Laju napas sedang</p>
                    </button>

                    <button type="button" @click="activity = 'jogging'; recalculate()"
                            :class="activity === 'jogging' ? 'border-[#2E9E3F] bg-[#2E9E3F] text-white shadow-md' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-[#2E9E3F]'"
                            class="rounded-2xl border p-3.5 text-left transition space-y-1">
                        <span class="text-xl">🏃</span>
                        <p class="text-xs font-extrabold">Jogging / Lari</p>
                        <p class="text-[11px] opacity-80">Laju napas tinggi (2.8 m³/jam)</p>
                    </button>

                    <button type="button" @click="activity = 'dalam_rumah'; recalculate()"
                            :class="activity === 'dalam_rumah' ? 'border-[#38A8E8] bg-[#38A8E8] text-white shadow-md' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-[#38A8E8]'"
                            class="rounded-2xl border p-3.5 text-left transition space-y-1">
                        <span class="text-xl">🏠</span>
                        <p class="text-xs font-extrabold">Di Dalam Rumah</p>
                        <p class="text-[11px] opacity-80">Terlindung sebagian (0.55x)</p>
                    </button>
                </div>
            </div>

            <!-- Duration Input -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-[#0B3566]">3. Durasi Aktivitas</label>
                    <span class="px-3 py-1 rounded-full bg-sky-50 text-[#0B3566] text-xs font-extrabold border border-sky-200"
                          x-text="formatDuration(durationMinutes)"></span>
                </div>

                <input type="range" min="10" max="360" step="5" x-model.number="durationMinutes" @input="recalculate()"
                       class="w-full accent-[#2E9E3F] cursor-pointer">

                <div class="flex flex-wrap gap-2">
                    <template x-for="preset in [30, 60, 90, 120, 180, 240]" :key="preset">
                        <button type="button" @click="durationMinutes = preset; recalculate()"
                                :class="durationMinutes === preset ? 'bg-[#0B3566] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition"
                                x-text="preset < 60 ? preset + ' mnt' : (preset / 60) + ' jam'"></button>
                    </template>
                </div>
            </div>

            <!-- Scientific Methodology Note -->
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5 text-[11px] text-slate-500 leading-relaxed">
                <strong class="text-[#0B3566]">Catatan Ilmiah:</strong> Estimasi menggunakan konsentrasi PM2.5 aktual di wilayah terpilih, faktor mikro-lingkungan (jalan raya vs indoor), volume ventilasi paru (EPA Exposure Factors), serta konversi analogi Berkeley Earth (22 µg/m³ selama 24 jam ≈ 1 batang rokok).
            </div>
        </div>

        <!-- Output Visualization & Practical Protection Tips -->
        <div class="lg:col-span-7 space-y-5">

            <!-- Main Exposure Result Banner -->
            <div class="rounded-2xl bg-gradient-to-br from-[#0B3566] to-[#134D8F] text-white p-6 shadow-lg space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-sky-200">Hasil Estimasi Paparan</p>
                        <h2 class="text-xl sm:text-2xl font-extrabold mt-0.5">
                            <span x-text="selectedRegionObj.name"></span> • <span x-text="result.activity_label"></span>
                        </h2>
                        <p class="text-xs text-sky-100 mt-0.5">
                            Durasi <strong x-text="formatDuration(durationMinutes)"></strong> • PM2.5 ambien <strong x-text="selectedRegionObj.pm25 + ' µg/m³'"></strong>
                        </p>
                    </div>
                    <span class="px-3 py-1.5 rounded-full text-xs font-extrabold"
                          :class="{
                              'bg-[#2E9E3F] text-white': result.risk.color === 'emerald',
                              'bg-amber-500 text-white': result.risk.color === 'amber',
                              'bg-orange-500 text-white': result.risk.color === 'orange',
                              'bg-red-600 text-white': result.risk.color === 'red'
                          }"
                          x-text="'Risiko ' + result.risk.label"></span>
                </div>

                <!-- 3 Key Metrics -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/15 p-4">
                        <p class="text-xs text-sky-100 font-semibold">Total PM2.5 Terhirup</p>
                        <p class="text-3xl font-extrabold mt-1">
                            <span x-text="result.total_inhaled_ug"></span>
                            <span class="text-sm font-semibold text-sky-200">µg</span>
                        </p>
                        <p class="text-[11px] text-sky-200 mt-1">Volume udara: <span x-text="result.inhaled_air_m3"></span> m³</p>
                    </div>

                    <div class="rounded-2xl bg-white text-[#0B3566] p-4 shadow-md">
                        <p class="text-xs text-slate-500 font-bold">Analogi Batang Rokok</p>
                        <p class="text-3xl font-extrabold text-orange-600 mt-1">
                            <span x-text="result.cigarettes"></span>
                            <span class="text-sm font-bold text-[#0B3566]">batang</span>
                        </p>
                        <p class="text-[11px] text-slate-500 mt-1">Dalam sesi ini (≈ <strong x-text="result.monthly_cigarettes"></strong> btg/bulan)</p>
                    </div>

                    <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/15 p-4">
                        <p class="text-xs text-sky-100 font-semibold">PM2.5 Efektif Terhirup</p>
                        <p class="text-3xl font-extrabold text-[#6CE07D] mt-1">
                            <span x-text="result.effective_pm25"></span>
                            <span class="text-sm font-semibold text-sky-200">µg/m³</span>
                        </p>
                        <p class="text-[11px] text-sky-200 mt-1" x-text="result.risk.desc"></p>
                    </div>
                </div>
            </div>

            <!-- Activity Comparison Chart -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base font-extrabold text-[#0B3566]">Perbandingan Paparan Antar Aktivitas</h3>
                        <p class="text-xs text-slate-500">Total PM2.5 terhirup (µg) untuk durasi <span class="font-bold" x-text="formatDuration(durationMinutes)"></span> di <span class="font-bold" x-text="selectedRegionObj.name"></span>.</p>
                    </div>
                    <span class="text-xs font-bold text-[#2E9E3F] bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                        Masker KF94/N95 mengurangi paparan hingga 80%
                    </span>
                </div>
                <div class="h-56 w-full">
                    <canvas id="exposureCompareChart"></canvas>
                </div>
            </div>

            <!-- Practical Protection Tips -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-extrabold text-[#0B3566]">Tips Perlindungan Praktis</h3>
                    <a href="{{ route('rute-bersih') }}" class="text-xs font-bold text-[#2E9E3F] hover:underline">Pilih Rute Bersih →</a>
                </div>
                <ul class="space-y-2.5">
                    <template x-for="(tip, idx) in result.tips" :key="idx">
                        <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100 text-sm text-slate-700">
                            <span class="w-6 h-6 rounded-full bg-[#2E9E3F] text-white flex items-center justify-center text-xs font-bold shrink-0" x-text="idx + 1"></span>
                            <span x-text="tip"></span>
                        </li>
                    </template>
                </ul>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const CALC_REGIONS = @json($regionsData);
    const INITIAL_REGION_SLUG = @json($selectedRegion->slug);
    const INITIAL_ACTIVITY = @json($activity);
    const INITIAL_DURATION = @json($durationMinutes);

    const ACTIVITY_PROFILES = {
        motor: {
            label: 'Naik Motor di Jalan Raya',
            multiplier: 1.65,
            ventilation: 0.85,
            tips: [
                'Gunakan masker KF94/N95 (bukan masker kain tipis) dan tutup kaca helm rapat saat berkendara.',
                'Hindari membuntuti langsung knalpot truk, bus, atau kendaraan berasap pekat di persimpangan.',
                'Gunakan fitur Rute Bersih NapasAman untuk memangkas paparan PM2.5 hingga 20–35%.'
            ]
        },
        jalan_kaki: {
            label: 'Jalan Kaki di Luar Ruangan',
            multiplier: 1.15,
            ventilation: 1.35,
            tips: [
                'Berjalan di sisi trotoar yang lebih jauh dari aspal atau jalur rindang berpohon.',
                'Gunakan masker medis/KF94 ketika melintasi persimpangan padat atau area konstruksi.',
                'Hindari berjalan kaki di dekat lokasi pembakaran sampah terbuka.'
            ]
        },
        jogging: {
            label: 'Jogging / Lari Luar Ruangan',
            multiplier: 1.10,
            ventilation: 2.80,
            tips: [
                'Pilih jam lari paling bersih (biasanya pukul 05.00–06.30 WIB) sebelum arus kendaraan padat.',
                'Lari di taman kota, stadion, atau jalan desa yang jauh dari arus kendaraan bermotor.',
                'Jika AQI > 100, kurangi intensitas atau pindahkan latihan ke dalam ruangan.'
            ]
        },
        dalam_rumah: {
            label: 'Aktivitas di Dalam Rumah',
            multiplier: 0.55,
            ventilation: 0.50,
            tips: [
                'Tutup jendela saat ada asap pembakaran sampah di sekitar lingkungan rumah.',
                'Hindari asap rokok dalam ruangan dan pastikan ventilasi dapur baik saat memasak.',
                'Buka jendela di pagi hari saat udara paling segar untuk pertukaran oksigen sehat.'
            ]
        }
    };

    let compareChart = null;

    function exposureCalculator() {
        return {
            regions: CALC_REGIONS,
            regionSlug: INITIAL_REGION_SLUG,
            activity: INITIAL_ACTIVITY,
            durationMinutes: INITIAL_DURATION,
            result: {
                activity_label: '',
                effective_pm25: 0,
                inhaled_air_m3: 0,
                total_inhaled_ug: 0,
                cigarettes: 0,
                monthly_cigarettes: 0,
                risk: { label: 'Rendah', color: 'emerald', desc: '' },
                tips: []
            },

            get selectedRegionObj() {
                return this.regions.find(r => r.slug === this.regionSlug) || this.regions[0];
            },

            init() {
                this.recalculate();
            },

            formatDuration(mins) {
                if (mins < 60) return `${mins} menit`;
                const h = Math.floor(mins / 60);
                const m = mins % 60;
                return m === 0 ? `${h} jam` : `${h} jam ${m} menit`;
            },

            computeSingle(ambientPm25, actKey, mins) {
                const p = ACTIVITY_PROFILES[actKey] || ACTIVITY_PROFILES.motor;
                const hours = Math.max(5, mins) / 60;
                const effectivePm25 = Number((ambientPm25 * p.multiplier).toFixed(1));
                const inhaledAirM3 = Number((p.ventilation * hours).toFixed(2));
                const totalInhaledUg = Number((effectivePm25 * inhaledAirM3).toFixed(1));
                const cigarettes = Number((totalInhaledUg / 264).toFixed(2));
                const monthlyCigarettes = Number((cigarettes * 30).toFixed(1));

                let risk = { label: 'Rendah', color: 'emerald', desc: 'Paparan masih tergolong ringan bagi paru-paru sehat.' };
                if (totalInhaledUg >= 160) {
                    risk = { label: 'Sangat Tinggi', color: 'red', desc: 'Berisiko memicu iritasi saluran napas; gunakan proteksi maksimal.' };
                } else if (totalInhaledUg >= 75) {
                    risk = { label: 'Tinggi', color: 'orange', desc: 'Disarankan memakai masker KF94/N95 atau mengurangi durasi.' };
                } else if (totalInhaledUg >= 25) {
                    risk = { label: 'Sedang', color: 'amber', desc: 'Paparan mulai signifikan bila dilakukan rutin setiap hari.' };
                }

                return {
                    activity_label: p.label,
                    effective_pm25: effectivePm25,
                    inhaled_air_m3: inhaledAirM3,
                    total_inhaled_ug: totalInhaledUg,
                    cigarettes: cigarettes,
                    monthly_cigarettes: monthlyCigarettes,
                    risk: risk,
                    tips: p.tips
                };
            },

            recalculate() {
                const pm25 = Number(this.selectedRegionObj.pm25);
                this.result = this.computeSingle(pm25, this.activity, this.durationMinutes);
                this.$nextTick(() => this.renderCompareChart(pm25));
            },

            renderCompareChart(pm25) {
                const canvas = document.getElementById('exposureCompareChart');
                if (!canvas) return;

                const keys = ['motor', 'jalan_kaki', 'jogging', 'dalam_rumah'];
                const labels = ['Naik Motor', 'Jalan Kaki', 'Jogging', 'Dalam Rumah'];
                const withoutMask = keys.map(k => this.computeSingle(pm25, k, this.durationMinutes).total_inhaled_ug);
                const withMask = withoutMask.map(v => Number((v * 0.2).toFixed(1)));

                if (compareChart) {
                    compareChart.data.datasets[0].data = withoutMask;
                    compareChart.data.datasets[1].data = withMask;
                    compareChart.update();
                    return;
                }

                compareChart = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Tanpa Masker (µg PM2.5)',
                                data: withoutMask,
                                backgroundColor: '#0B3566',
                                borderRadius: 10,
                            },
                            {
                                label: 'Dengan Masker KF94/N95 (µg PM2.5)',
                                data: withMask,
                                backgroundColor: '#2E9E3F',
                                borderRadius: 10,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { usePointStyle: true, font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' } }
                            }
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#F1F5F9' },
                                ticks: { callback: (v) => v + ' µg' }
                            }
                        }
                    }
                });
            }
        }
    }
</script>
@endpush
