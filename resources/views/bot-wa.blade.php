@extends('layouts.app')

@section('title', 'WhatsApp Bot Sobat Napas (+62 895-3668-66722) — NapasAman')

@section('content')
<div x-data="{ customText: @js($defaultInitialMessage), botNumber: @js($botNumber) }"
     class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-8">

    <!-- Hero Direct WhatsApp Card -->
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#075E54] via-[#0B6E57] to-[#2E9E3F] text-white p-6 sm:p-8 lg:p-10 shadow-xl">
        <div class="absolute -right-16 -top-16 w-72 h-72 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 rounded-full bg-[#38A8E8]/20 blur-3xl pointer-events-none"></div>

        <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-[#6CE07D] animate-pulse"></span>
                    WhatsApp Bot Asli • Terhubung ke Gemini AI &amp; Data MySQL Kediri Raya
                </div>

                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                    Chat Langsung dengan<br>
                    <span class="text-[#B8F7C2]">Sobat Napas di WhatsApp</span>
                </h1>

                <p class="text-sm sm:text-base text-emerald-50/90 max-w-2xl leading-relaxed">
                    Tidak perlu install aplikasi tambahan. Klik tombol di bawah untuk langsung masuk ke aplikasi WhatsApp resmi kami di nomor <strong>{{ $formattedNumber }}</strong> — pesan awal sudah terisi otomatis, Anda tinggal tekan <strong>Kirim</strong>!
                </p>

                <!-- Official Phone Number Pill + Primary Direct WA CTA -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 pt-2">
                    <a href="{{ $primaryWaUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-2xl bg-white hover:bg-emerald-50 text-[#075E54] text-sm sm:text-base font-extrabold shadow-lg transition">
                        <svg class="w-5 h-5 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24"><path d="M20.52 3.48A11.86 11.86 0 0012.06 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.15 1.6 5.95L0 24l6.32-1.66a11.84 11.84 0 005.74 1.47h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.43zM12.07 21.8h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.75.98 1-3.66-.23-.38a9.84 9.84 0 01-1.51-5.25c0-5.44 4.43-9.88 9.89-9.88 2.64 0 5.12 1.03 6.98 2.9a9.82 9.82 0 012.89 6.99c0 5.45-4.43 9.89-9.87 9.89zm5.42-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.21 5.09 4.5.71.31 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.09 1.76-.72 2.01-1.42.25-.7.25-1.29.17-1.42-.07-.12-.27-.2-.57-.35z"/></svg>
                        Buka WhatsApp Sekarang
                    </a>

                    <div class="inline-flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-black/20 backdrop-blur border border-white/15">
                        <span class="text-xs text-emerald-100">Nomor Resmi:</span>
                        <span class="font-mono font-extrabold text-sm tracking-wide">{{ $formattedNumber }}</span>
                    </div>
                </div>
            </div>

            <!-- Interactive Pre-filled Auto Message Composer -->
            <div class="lg:col-span-5">
                <div class="rounded-2xl bg-white text-slate-900 p-5 sm:p-6 shadow-2xl space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 p-1.5 border border-emerald-200 flex items-center justify-center shrink-0">
                            <img src="{{ asset('images/icon-192.png') }}" alt="Sobat Napas" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h2 class="font-extrabold text-[#0B3566] text-base leading-tight">Sobat Napas — NapasAman</h2>
                            <p class="text-xs font-mono text-[#2E9E3F] font-bold">{{ $formattedNumber }}</p>
                            <p class="text-[11px] text-slate-500">{{ $totalInteractions }} interaksi • {{ $uniqueUsers }} warga aktif</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="customWaMsg" class="block text-xs font-bold text-[#0B3566]">
                            Pesan Awal Otomatis (bisa langsung kirim atau ubah dulu):
                        </label>
                        <textarea id="customWaMsg" x-model="customText" rows="3"
                                  class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-[#2E9E3F] focus:bg-white focus:outline-none"></textarea>
                    </div>

                    <a :href="'https://wa.me/' + botNumber + '?text=' + encodeURIComponent(customText.trim() || 'Halo Sobat Napas')"
                       target="_blank" rel="noopener noreferrer"
                       class="w-full py-3 px-4 rounded-xl bg-[#25D366] hover:bg-[#1EB958] text-white font-extrabold text-sm shadow-md transition flex items-center justify-center gap-2">
                        💬 Lanjut ke Aplikasi WhatsApp →
                    </a>

                    <p class="text-[11px] text-slate-500 text-center leading-relaxed">
                        Begitu aplikasi WhatsApp terbuka, pesan di atas otomatis terisi di kolom chat. Tekan tombol <strong>Kirim</strong> dan Sobat Napas langsung membalas otomatis!
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Pre-filled WhatsApp Actions -->
    <section class="space-y-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-[#0B3566]">Pilih Topik Chat Siap Kirim di WhatsApp</h2>
            <p class="text-sm text-slate-500">Klik salah satu kartu di bawah untuk langsung membuka chat WhatsApp ke <strong>{{ $formattedNumber }}</strong> dengan pesan otomatis sesuai kebutuhan Anda.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($quickLinks as $item)
                @php
                    $waLink = 'https://wa.me/' . $botNumber . '?text=' . rawurlencode($item['text']);
                    $badgeClass = match($item['color']) {
                        'emerald' => 'bg-emerald-50 text-[#2E9E3F] border-emerald-200',
                        'sky' => 'bg-sky-50 text-[#0B3566] border-sky-200',
                        'orange' => 'bg-orange-50 text-orange-700 border-orange-200',
                        default => 'bg-[#0B3566] text-white border-[#0B3566]',
                    };
                @endphp
                <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
                   class="group rounded-2xl bg-white border border-slate-200 hover:border-[#2E9E3F] hover:shadow-md p-5 transition flex flex-col justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeClass }}">{{ $item['badge'] }}</span>
                            <span class="text-xs font-bold text-[#2E9E3F] group-hover:translate-x-0.5 transition">Buka WA →</span>
                        </div>
                        <h3 class="font-extrabold text-[#0B3566] text-base">{{ $item['title'] }}</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">{{ $item['desc'] }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-200/80 px-3 py-2 text-[11px] font-mono text-slate-700">
                        "{{ $item['text'] }}"
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <!-- Capabilities & How It Works -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-extrabold text-[#0B3566]">Fitur Cerdas di WhatsApp Sobat Napas</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-4 rounded-2xl bg-sky-50/70 border border-sky-200 space-y-1.5">
                    <div class="text-xl">📍</div>
                    <p class="font-extrabold text-[#0B3566]">Share Location</p>
                    <p class="text-slate-600 leading-relaxed">Kirim lokasi GPS Anda di WA untuk cek AQI &amp; titik asap terdekat dari 7 wilayah Kediri Raya.</p>
                </div>
                <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-1.5">
                    <div class="text-xl">🤖</div>
                    <p class="font-extrabold text-[#2E9E3F]">Tanya Bebas Gemini AI</p>
                    <p class="text-slate-600 leading-relaxed">Tanya santai seperti <em>"Aman gak ajak anak ke Alun-Alun Blitar?"</em> — dijawab memakai data asli MySQL.</p>
                </div>
                <div class="p-4 rounded-2xl bg-orange-50/70 border border-orange-200 space-y-1.5">
                    <div class="text-xl">📷</div>
                    <p class="font-extrabold text-orange-700">Foto + Caption LAPOR</p>
                    <p class="text-slate-600 leading-relaxed">Kirim foto pembakaran sampah dengan caption <code>LAPOR [Lokasi]</code> — otomatis tampil di Peta Rute Bersih!</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-5 bg-[#0B3566] text-white rounded-2xl p-6 shadow-md space-y-3">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-base font-extrabold">Status Integrasi Backend</h3>
                <span class="px-2.5 py-0.5 rounded-full {{ $geminiEnabled ? 'bg-[#2E9E3F]' : 'bg-amber-500' }} text-[10px] font-bold">
                    {{ $geminiEnabled ? 'Gemini AI Aktif' : 'Rule Fallback' }}
                </span>
            </div>
            <ul class="space-y-2 text-xs text-sky-100">
                <li>• <strong>Nomor WA Bot:</strong> <span class="font-mono text-[#6CE07D]">{{ $formattedNumber }}</span></li>
                <li>• <strong>Endpoint Webhook:</strong> <span class="font-mono text-sky-200 break-all">{{ $webhookUrl }}</span></li>
                <li>• <strong>Keamanan Webhook:</strong> Proteksi header <code>X-Webhook-Secret</code> &amp; verifikasi token Meta Cloud API.</li>
                <li>• <strong>Database:</strong> Seluruh chat tersimpan di <code>whatsapp_logs</code> &amp; foto laporan di <code>smoke_reports</code>.</li>
            </ul>
        </div>
    </section>

</div>
@endsection
