<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B3566">
    <meta name="description" content="NapasAman — Cek Kualitas Udara. Hidup Lebih Sehat. Pantauan kualitas udara PM2.5 real-time, rute rendah polusi, kalkulator paparan, dan lapor titik asap khusus Wilayah Eks-Karesidenan Kediri.">

    <title>@yield('title', 'NapasAman — Cek Kualitas Udara. Hidup Lebih Sehat.')</title>

    <link rel="icon" type="image/png" href="{{ asset('images/napasaman-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icon-192.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN with NapasAman Theme -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        navy: {
                            DEFAULT: '#0B3566',
                            50: '#EEF5FC',
                            100: '#D7E7F8',
                            600: '#144B8A',
                            700: '#0B3566',
                            800: '#08274C',
                            900: '#051A34',
                        },
                        napasgreen: {
                            DEFAULT: '#2E9E3F',
                            50: '#EEF9F0',
                            100: '#D6F2DA',
                            600: '#2E9E3F',
                            700: '#237E31',
                        },
                        skyaccent: {
                            DEFAULT: '#38A8E8',
                            50: '#EDF8FE',
                            100: '#D6EFFC',
                            500: '#38A8E8',
                            600: '#1F8FD0',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #F8FAFC;
            color: #0F172A;
        }
    </style>

    @stack('styles')
</head>
<body class="min-h-screen flex flex-col antialiased pb-20 md:pb-0" x-data="{ mobileMenu: false, installPrompt: null, showInstallBtn: false }"
      x-init="
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            installPrompt = e;
            showInstallBtn = true;
        });
      ">

    <!-- Top Region Scope Strip -->
    <div class="bg-[#0B3566] text-white text-xs py-1.5 px-4">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-[#2E9E3F] animate-pulse"></span>
                <span class="font-medium">Fokus Wilayah Eks-Karesidenan Kediri (7 Daerah: Kota/Kab. Kediri, Tulungagung, Kota/Kab. Blitar, Nganjuk, Trenggalek)</span>
            </div>
            <div class="hidden sm:flex items-center gap-3 text-sky-200">
                <span>AIRIMPACT 2026 • Tim JosJiz AG</span>
            </div>
        </div>
    </div>

    <!-- Main Navbar -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20 gap-4">
                <!-- Brand Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0 group">
                    <img src="{{ asset('images/napasaman-logo.png') }}"
                         alt="Logo NapasAman"
                         class="h-11 md:h-13 w-auto object-contain transition-transform group-hover:scale-[1.02]">
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center gap-1">
                    @php
                        $navItems = [
                            ['route' => 'home', 'label' => 'Beranda', 'match' => 'home'],
                            ['route' => 'rute-bersih', 'label' => 'Rute Bersih', 'match' => 'rute-bersih'],
                            ['route' => 'kalkulator-paparan', 'label' => 'Kalkulator PM2.5', 'match' => 'kalkulator-paparan'],
                            ['route' => 'lapor-asap.index', 'label' => 'Lapor Asap', 'match' => 'lapor-asap.*'],
                            ['route' => 'bot-wa.index', 'label' => 'Bot WA', 'match' => 'bot-wa.*'],
                        ];
                    @endphp

                    @foreach ($navItems as $item)
                        @php $active = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all {{ $active ? 'bg-[#0B3566] text-white shadow-sm' : 'text-[#0B3566] hover:bg-slate-100' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <!-- Right Actions -->
                <div class="flex items-center gap-2">
                    <!-- PWA Install Button -->
                    <button x-show="showInstallBtn" x-cloak
                            @click="if (installPrompt) { installPrompt.prompt(); installPrompt.userChoice.then(() => { showInstallBtn = false; installPrompt = null; }); }"
                            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#38A8E8]/15 text-[#0B3566] hover:bg-[#38A8E8]/25 text-xs font-bold transition">
                        <svg class="w-4 h-4 text-[#38A8E8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
                        Install PWA
                    </button>

                    @auth
                        @if(auth()->user()->isAdminOrMitra())
                            <a href="{{ route('admin.dashboard') }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold transition {{ request()->routeIs('admin.*') ? 'bg-[#2E9E3F] text-white' : 'bg-[#2E9E3F]/10 text-[#2E9E3F] hover:bg-[#2E9E3F] hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                                <span class="hidden sm:inline">Panel Mitra</span>
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                                Keluar
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-[#0B3566] text-[#0B3566] hover:text-white text-xs sm:text-sm font-bold transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            <span>Panel Mitra</span>
                        </a>
                    @endauth

                    <!-- Mobile Menu Toggle -->
                    <button @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 rounded-xl text-[#0B3566] hover:bg-slate-100" aria-label="Buka Menu">
                        <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileMenu" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Dropdown -->
        <div x-show="mobileMenu" x-cloak x-transition class="lg:hidden border-t border-slate-100 bg-white px-4 py-3 space-y-1.5 shadow-lg">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="block px-4 py-2.5 rounded-xl text-sm font-semibold {{ $active ? 'bg-[#0B3566] text-white' : 'text-[#0B3566] hover:bg-slate-50' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
            @auth
                <form method="POST" action="{{ route('logout') }}" class="pt-1 border-t border-slate-100">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold text-red-600 hover:bg-red-50">
                        Keluar ({{ auth()->user()->name }})
                    </button>
                </form>
            @endauth
        </div>
    </header>

    <!-- Flash Alerts -->
    @if (session('success') || session('error'))
        <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            @if (session('success'))
                <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center gap-3 shadow-sm">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-[#2E9E3F] text-white flex items-center justify-center font-bold">✓</span>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 flex items-center gap-3 shadow-sm">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-red-600 text-white flex items-center justify-center font-bold">!</span>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif
        </div>
    @endif

    <!-- Page Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                <div class="space-y-3">
                    <img src="{{ asset('images/napasaman-logo.png') }}" alt="Logo NapasAman" class="h-12 w-auto object-contain">
                    <p class="text-sm font-semibold text-[#0B3566]">Cek Kualitas Udara. Hidup Lebih Sehat.</p>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Solusi pemantauan kualitas udara berbasis data, navigasi rute rendah polusi, dan pelaporan titik asap partisipatif khusus <strong>Wilayah Eks-Karesidenan Kediri</strong>.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-[#0B3566] mb-3">7 Wilayah Cakupan</h3>
                    <div class="flex flex-wrap gap-1.5 text-xs">
                        @foreach (['Kota Kediri', 'Kab. Kediri', 'Kab. Tulungagung', 'Kota Blitar', 'Kab. Blitar', 'Kab. Nganjuk', 'Kab. Trenggalek'] as $w)
                            <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-medium">{{ $w }}</span>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-[#0B3566] mb-3">Akses Cepat</h3>
                    <ul class="space-y-1.5 text-xs text-slate-600 font-medium">
                        <li><a href="{{ route('rute-bersih') }}" class="hover:text-[#2E9E3F]">Peta Navigasi Rute Rendah Polusi</a></li>
                        <li><a href="{{ route('kalkulator-paparan') }}" class="hover:text-[#2E9E3F]">Kalkulator Paparan PM2.5</a></li>
                        <li><a href="{{ route('lapor-asap.index') }}" class="hover:text-[#2E9E3F]">Crowdsourcing Lapor Titik Asap</a></li>
                        <li><a href="{{ route('bot-wa.index') }}" class="hover:text-[#2E9E3F]">WhatsApp Bot "Sobat Napas"</a></li>
                        <li><a href="{{ route('admin.dashboard') }}" class="hover:text-[#2E9E3F]">Panel Mitra RT/RW & Bank Sampah</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
                <p>© {{ date('Y') }} <strong class="text-[#0B3566]">NapasAman</strong> — AIRIMPACT 2026 (Tim JosJiz AG).</p>
                <p>Data kualitas udara: Open-Meteo Air Quality API • Peta: OpenStreetMap & OSRM</p>
            </div>
        </div>
    </footer>

    <!-- Mobile Bottom PWA Navigation -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200 px-2 py-1.5 grid grid-cols-5 gap-1 shadow-[0_-4px_20px_rgba(11,53,102,0.08)]">
        <a href="{{ route('home') }}" class="flex flex-col items-center py-1 rounded-xl text-[11px] font-semibold {{ request()->routeIs('home') ? 'text-[#2E9E3F] bg-emerald-50' : 'text-slate-500' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Beranda
        </a>
        <a href="{{ route('rute-bersih') }}" class="flex flex-col items-center py-1 rounded-xl text-[11px] font-semibold {{ request()->routeIs('rute-bersih') ? 'text-[#2E9E3F] bg-emerald-50' : 'text-slate-500' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            Rute
        </a>
        <a href="{{ route('kalkulator-paparan') }}" class="flex flex-col items-center py-1 rounded-xl text-[11px] font-semibold {{ request()->routeIs('kalkulator-paparan') ? 'text-[#2E9E3F] bg-emerald-50' : 'text-slate-500' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Hitung
        </a>
        <a href="{{ route('lapor-asap.index') }}" class="flex flex-col items-center py-1 rounded-xl text-[11px] font-semibold {{ request()->routeIs('lapor-asap.*') ? 'text-[#2E9E3F] bg-emerald-50' : 'text-slate-500' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Lapor
        </a>
        <a href="{{ route('bot-wa.index') }}" class="flex flex-col items-center py-1 rounded-xl text-[11px] font-semibold {{ request()->routeIs('bot-wa.*') ? 'text-[#2E9E3F] bg-emerald-50' : 'text-slate-500' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            Bot WA
        </a>
    </nav>

    <!-- Floating Direct WhatsApp Button (Sobat Napas +62 895-3668-66722) -->
    @php
        $fabWaNumber = preg_replace('/[^0-9]/', '', (string) config('services.whatsapp.bot_number', '62895366866722')) ?: '62895366866722';
        $fabWaText = rawurlencode('Halo Sobat Napas, saya ingin cek kualitas udara di wilayah Eks-Karesidenan Kediri');
    @endphp
    <a href="https://wa.me/{{ $fabWaNumber }}?text={{ $fabWaText }}"
       target="_blank" rel="noopener noreferrer"
       title="Chat langsung Sobat Napas di WhatsApp (+62 895-3668-66722)"
       class="fixed right-4 bottom-20 md:bottom-6 z-40 inline-flex items-center gap-2 px-4 py-3 rounded-full bg-[#25D366] hover:bg-[#1EB958] text-white font-extrabold text-xs sm:text-sm shadow-xl shadow-emerald-900/25 transition hover:scale-105">
        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M20.52 3.48A11.86 11.86 0 0012.06 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.15 1.6 5.95L0 24l6.32-1.66a11.84 11.84 0 005.74 1.47h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.43zM12.07 21.8h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.75.98 1-3.66-.23-.38a9.84 9.84 0 01-1.51-5.25c0-5.44 4.43-9.88 9.89-9.88 2.64 0 5.12 1.03 6.98 2.9a9.82 9.82 0 012.89 6.99c0 5.45-4.43 9.89-9.87 9.89zm5.42-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.21 5.09 4.5.71.31 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.09 1.76-.72 2.01-1.42.25-.7.25-1.29.17-1.42-.07-.12-.27-.2-.57-.35z"/></svg>
        <span class="hidden sm:inline">Chat WA Sobat Napas</span>
    </a>

    <!-- Register PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
