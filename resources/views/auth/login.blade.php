@extends('layouts.app')

@section('title', 'Masuk Panel Mitra & Admin — NapasAman')

@section('content')
<div class="max-w-md mx-auto px-4 py-10 sm:py-14">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl p-6 sm:p-8 space-y-6">
        <!-- Logo & Tagline -->
        <div class="text-center space-y-2">
            <img src="{{ asset('images/napasaman-logo.png') }}" alt="Logo NapasAman" class="h-16 w-auto mx-auto object-contain">
            <h1 class="text-xl font-extrabold text-[#0B3566] pt-1">Panel Admin &amp; Mitra RT/RW</h1>
            <p class="text-xs text-slate-500">Masuk untuk memantau KPI dampak &amp; menindaklanjuti laporan titik asap di Eks-Karesidenan Kediri.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700 font-medium">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-bold text-[#0B3566] mb-1.5">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="admin@napasaman.id"
                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-[#38A8E8] focus:bg-white focus:outline-none">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-[#0B3566] mb-1.5">Kata Sandi</label>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••"
                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-[#38A8E8] focus:bg-white focus:outline-none">
            </div>

            <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#2E9E3F] focus:ring-[#2E9E3F]">
                Ingat sesi saya
            </label>

            <button type="submit"
                    class="w-full py-3 rounded-xl bg-[#0B3566] hover:bg-[#08274C] text-white font-bold text-sm shadow-md transition">
                Masuk ke Panel Mitra
            </button>
        </form>

        <!-- Demo Accounts Box -->
        <div class="rounded-2xl bg-sky-50/70 border border-sky-200 p-4 space-y-2.5">
            <p class="text-xs font-extrabold text-[#0B3566]">Akun Demo AIRIMPACT 2026 (Klik untuk isi cepat):</p>
            <div class="grid grid-cols-1 gap-2">
                <button type="button" onclick="fillDemo('admin@napasaman.id', 'napasaman2026')"
                        class="text-left px-3 py-2 rounded-xl bg-white hover:bg-emerald-50 border border-slate-200 text-xs transition">
                    <span class="font-extrabold text-[#0B3566]">Admin NapasAman</span>
                    <span class="block text-[11px] text-slate-500 font-mono">admin@napasaman.id / napasaman2026</span>
                </button>
                <button type="button" onclick="fillDemo('mitra@napasaman.id', 'napasaman2026')"
                        class="text-left px-3 py-2 rounded-xl bg-white hover:bg-emerald-50 border border-slate-200 text-xs transition">
                    <span class="font-extrabold text-[#2E9E3F]">Mitra RT/RW &amp; Bank Sampah</span>
                    <span class="block text-[11px] text-slate-500 font-mono">mitra@napasaman.id / napasaman2026</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function fillDemo(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endpush
