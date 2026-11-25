<?php

use App\Http\Controllers\WhatsappBotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — NapasAman (Eks-Karesidenan Kediri)
|--------------------------------------------------------------------------
*/

// Webhook WhatsApp Bot "Sobat Napas" (Fonnte & Meta WhatsApp Cloud API)
Route::get('/whatsapp/webhook', [WhatsappBotController::class, 'verifyWebhook'])->name('api.whatsapp.verify');
Route::post('/whatsapp/webhook', [WhatsappBotController::class, 'webhook'])->name('api.whatsapp.webhook');
