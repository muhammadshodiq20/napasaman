<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CleanRouteController;
use App\Http\Controllers\ExposureCalculatorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SmokeReportController;
use App\Http\Controllers\WhatsappBotController;
use Illuminate\Support\Facades\Route;

// 1. Beranda & Dashboard Kualitas Udara Kediri Raya
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/api/wilayah/{region:slug}/kualitas-udara', [HomeController::class, 'regionJson'])->name('region.aq.json');

// 2. Peta Navigasi Rute Rendah Polusi
Route::get('/rute-bersih', [CleanRouteController::class, 'index'])->name('rute-bersih');

// 3. Kalkulator & Visualisasi Paparan PM2.5
Route::get('/kalkulator-paparan', [ExposureCalculatorController::class, 'index'])->name('kalkulator-paparan');
Route::post('/kalkulator-paparan/hitung', [ExposureCalculatorController::class, 'calculate'])->name('kalkulator-paparan.hitung');

// 4. Crowdsourcing Lapor Titik Asap
Route::get('/lapor-asap', [SmokeReportController::class, 'index'])->name('lapor-asap.index');
Route::post('/lapor-asap', [SmokeReportController::class, 'store'])->name('lapor-asap.store');

// 5. WhatsApp Bot "Sobat Napas" (Direct ke WhatsApp +62 895-3668-66722)
Route::get('/bot-wa', [WhatsappBotController::class, 'index'])->name('bot-wa.index');

// 6. Autentikasi & Panel Admin / Mitra RT-RW & Bank Sampah
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::patch('/laporan/{smokeReport}/status', [AdminController::class, 'updateReportStatus'])->name('admin.reports.update-status');
});
