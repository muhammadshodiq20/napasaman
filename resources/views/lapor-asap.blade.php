@extends('layouts.app')

@section('title', 'Lapor Titik Asap — NapasAman Kediri Raya')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #reportMap { height: 300px; width: 100%; border-radius: 0.875rem; z-index: 10; }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-50 text-orange-700 text-xs font-bold border border-orange-200">
                🔥 Crowdsourcing Warga Kediri Raya
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0B3566] mt-2">Lapor Titik Asap &amp; Polusi Lokal</h1>
            <p class="text-sm text-slate-600 mt-1 max-w-2xl">
                Laporkan pembakaran sampah terbuka, asap kendaraan pekat, atau debu konstruksi di wilayah Eks-Karesidenan Kediri. Laporan aktif otomatis muncul di <strong>Peta Rute Bersih</strong> dan diteruskan ke <strong>Mitra RT/RW &amp; Bank Sampah</strong>.
            </p>
        </div>
        <a href="{{ route('rute-bersih') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#0B3566] hover:bg-[#08274C] text-white text-xs font-bold transition shrink-0">
            🗺️ Lihat Hotspot di Peta Rute Bersih
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Form Card -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <h2 class="text-lg font-extrabold text-[#0B3566] mb-1">Form Pelaporan Cepat</h2>
            <p class="text-xs text-slate-500 mb-5">Klik peta atau gunakan GPS untuk menandai lokasi kejadian di Eks-Karesidenan Kediri.</p>

            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-3.5 text-xs text-red-700 space-y-1">
                    <p class="font-bold">Mohon periksa kembali isian Anda:</p>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('lapor-asap.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <!-- Region -->
                <div>
                    <label for="region_id" class="block text-xs font-bold text-[#0B3566] mb-1.5">Wilayah (Eks-Karesidenan Kediri)</label>
                    <select id="region_id" name="region_id" required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-[#38A8E8] focus:bg-white focus:outline-none">
                        @foreach ($regions as $reg)
                            <option value="{{ $reg->id }}"
                                    data-lat="{{ $reg->latitude }}"
                                    data-lng="{{ $reg->longitude }}"
                                    {{ old('region_id') == $reg->id ? 'selected' : '' }}>
                                {{ $reg->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Map Picker -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-bold text-[#0B3566]">Pilih Titik Lokasi di Peta</label>
                        <button type="button" id="btnUseGps"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-50 hover:bg-sky-100 text-[#0B3566] text-[11px] font-bold border border-sky-200 transition">
                            📍 Gunakan GPS Saya
                        </button>
                    </div>
                    <div id="reportMap" class="border border-slate-200"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Latitude</label>
                            <input type="text" id="latitude" name="latitude" readonly required
                                   value="{{ old('latitude', '-7.816600') }}"
                                   class="w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-mono text-slate-700">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Longitude</label>
                            <input type="text" id="longitude" name="longitude" readonly required
                                   value="{{ old('longitude', '112.011400') }}"
                                   class="w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-mono text-slate-700">
                        </div>
                    </div>
                    <p id="gpsStatus" class="text-[11px] text-slate-500"></p>
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-xs font-bold text-[#0B3566] mb-1.5">Kategori Sumber Polusi</label>
                    <select id="category" name="category" required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-[#38A8E8] focus:bg-white focus:outline-none">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reporter Name & Phone -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="reporter_name" class="block text-xs font-bold text-[#0B3566] mb-1.5">Nama Pelapor</label>
                        <input type="text" id="reporter_name" name="reporter_name" required
                               value="{{ old('reporter_name') }}"
                               placeholder="Misal: Andi Pratama"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm focus:border-[#38A8E8] focus:bg-white focus:outline-none">
                    </div>
                    <div>
                        <label for="reporter_phone" class="block text-xs font-bold text-[#0B3566] mb-1.5">No. WhatsApp (Opsional)</label>
                        <input type="text" id="reporter_phone" name="reporter_phone"
                               value="{{ old('reporter_phone') }}"
                               placeholder="0812xxxxxxxx"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm focus:border-[#38A8E8] focus:bg-white focus:outline-none">
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-xs font-bold text-[#0B3566] mb-1.5">Deskripsi Singkat &amp; Patokan Lokasi</label>
                    <textarea id="description" name="description" rows="3" required
                              placeholder="Contoh: Pembakaran sampah plastik di lahan kosong dekat pertigaan Jl. Brawijaya, asap tebal mengarah ke jalan."
                              class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm focus:border-[#38A8E8] focus:bg-white focus:outline-none">{{ old('description') }}</textarea>
                </div>

                <!-- Photo Upload -->
                <div>
                    <label for="photo" class="block text-xs font-bold text-[#0B3566] mb-1.5">Foto Bukti (Opsional, maks 3 MB)</label>
                    <input type="file" id="photo" name="photo" accept="image/*"
                           class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-[#0B3566] hover:file:bg-sky-100 cursor-pointer border border-slate-200 rounded-xl p-1.5">
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-[#2E9E3F] hover:bg-[#237E31] text-white font-bold text-sm shadow-md shadow-emerald-600/20 transition">
                    Kirim Laporan Titik Asap
                </button>
            </form>
        </div>

        <!-- Public Reports List -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-[#0B3566]">Daftar Laporan Publik</h2>
                    <p class="text-xs text-slate-500">Transparansi pelaporan &amp; tindak lanjut RT/RW dan Bank Sampah.</p>
                </div>

                <form method="GET" action="{{ route('lapor-asap.index') }}" class="flex flex-wrap items-center gap-2">
                    <select name="region_id" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-[#0B3566]">
                        <option value="">Semua Wilayah</option>
                        @foreach ($regions as $reg)
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

            <div class="space-y-3">
                @forelse ($reports as $report)
                    <article class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm hover:border-[#38A8E8]/60 transition space-y-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-extrabold text-[#0B3566]">#NA-{{ $report->id }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-[#0B3566] border border-sky-200">
                                        {{ $report->region->name ?? 'Kediri Raya' }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-700 border border-orange-200">
                                        {{ $report->category }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500">
                                    Dilaporkan oleh <strong class="text-slate-700">{{ $report->reporter_name }}</strong> • {{ $report->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $report->status_badge_class }}">
                                {{ $report->status_label }}
                            </span>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4">
                            @if ($report->photo_url)
                                <img src="{{ $report->photo_url }}" alt="Foto Bukti #NA-{{ $report->id }}"
                                     class="w-full sm:w-32 h-28 object-cover rounded-xl border border-slate-200 shrink-0">
                            @endif
                            <div class="space-y-2 flex-1">
                                <p class="text-sm text-slate-700 leading-relaxed">{{ $report->description }}</p>
                                <p class="text-[11px] font-mono text-slate-400">
                                    Koordinat: {{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}
                                </p>

                                @if ($report->admin_notes)
                                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                                        <strong>Tindak Lanjut Mitra RT/RW &amp; Bank Sampah:</strong> {{ $report->admin_notes }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-sm text-slate-500">
                        Belum ada laporan untuk filter yang dipilih.
                    </div>
                @endforelse
            </div>

            <div class="pt-2">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const REPORT_BOUNDS = @json($bounds);
    const EXISTING_HOTSPOTS = @json($activeMapReports);

    const maxBounds = L.latLngBounds(
        [REPORT_BOUNDS.south, REPORT_BOUNDS.west],
        [REPORT_BOUNDS.north, REPORT_BOUNDS.east]
    );

    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const initLat = parseFloat(latInput.value) || -7.8166;
    const initLng = parseFloat(lngInput.value) || 112.0114;

    const reportMap = L.map('reportMap', {
        center: [initLat, initLng],
        zoom: 12,
        minZoom: 9,
        maxZoom: 18,
        maxBounds: maxBounds,
        maxBoundsViscosity: 1.0
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(reportMap);

    // Existing active hotspots
    EXISTING_HOTSPOTS.forEach((h) => {
        L.circleMarker([h.lat, h.lng], {
            radius: 6,
            color: '#DC2626',
            fillColor: '#EF4444',
            fillOpacity: 0.7
        }).addTo(reportMap).bindTooltip(`${h.category} (${h.region})`);
    });

    let pinMarker = L.marker([initLat, initLng], { draggable: true }).addTo(reportMap);

    function setPin(lat, lng) {
        if (!maxBounds.contains([lat, lng])) {
            document.getElementById('gpsStatus').textContent = 'Koordinat berada di luar batas wilayah Eks-Karesidenan Kediri.';
            return false;
        }
        pinMarker.setLatLng([lat, lng]);
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
        reportMap.panTo([lat, lng]);
        return true;
    }

    reportMap.on('click', (e) => {
        setPin(e.latlng.lat, e.latlng.lng);
        document.getElementById('gpsStatus').textContent = 'Titik lokasi dipilih dari peta.';
    });

    pinMarker.on('dragend', () => {
        const pos = pinMarker.getLatLng();
        setPin(pos.lat, pos.lng);
    });

    document.getElementById('region_id').addEventListener('change', (e) => {
        const opt = e.target.options[e.target.selectedIndex];
        const lat = parseFloat(opt.dataset.lat);
        const lng = parseFloat(opt.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng)) {
            setPin(lat, lng);
            reportMap.setView([lat, lng], 12);
        }
    });

    document.getElementById('btnUseGps').addEventListener('click', () => {
        const status = document.getElementById('gpsStatus');
        if (!navigator.geolocation) {
            status.textContent = 'Browser tidak mendukung GPS. Silakan klik langsung pada peta.';
            return;
        }
        status.textContent = 'Mendeteksi lokasi GPS Anda...';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const ok = setPin(pos.coords.latitude, pos.coords.longitude);
                if (ok) {
                    reportMap.setView([pos.coords.latitude, pos.coords.longitude], 14);
                    status.textContent = 'Lokasi GPS berhasil diterapkan di wilayah Eks-Karesidenan Kediri.';
                }
            },
            () => {
                status.textContent = 'Izin GPS ditolak atau tidak tersedia. Silakan klik langsung lokasi pada peta.';
            },
            { timeout: 6000 }
        );
    });
</script>
@endpush
