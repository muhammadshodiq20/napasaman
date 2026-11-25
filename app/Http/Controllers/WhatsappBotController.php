<?php

namespace App\Http\Controllers;

use App\Models\WhatsappLog;
use App\Services\GeminiAirQualityService;
use App\Services\WhatsappBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WhatsappBotController extends Controller
{
    public function __construct(
        protected WhatsappBotService $botService,
        protected GeminiAirQualityService $geminiService
    ) {}

    public function index(): View
    {
        $recentLogs = WhatsappLog::latest()->limit(8)->get()->reverse()->values();

        return view('bot-wa', [
            'recentLogs' => $recentLogs,
            'webhookUrl' => url('/api/whatsapp/webhook'),
            'geminiEnabled' => $this->geminiService->isConfigured(),
            'geminiModel' => config('services.gemini.model', 'gemini-2.5-flash'),
        ]);
    }

    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $phone = $validated['phone'] ?: '6281234567890';
        $result = $this->botService->handleMessage($phone, $validated['message']);

        return response()->json($result);
    }

    /**
     * GET /api/whatsapp/webhook — Meta WhatsApp Cloud API verification & health check.
     */
    public function verifyWebhook(Request $request): Response|JsonResponse
    {
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');
        if ($challenge) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json([
            'status' => 'ok',
            'service' => 'NapasAman Sobat Napas WhatsApp Webhook (Baileys + Gemini AI)',
            'gemini_configured' => $this->geminiService->isConfigured(),
            'gemini_model' => config('services.gemini.model', 'gemini-2.5-flash'),
            'region_scope' => 'Eks-Karesidenan Kediri (7 Wilayah)',
            'supported_inputs' => [
                'Teks bebas (Gemini AI)',
                'Share Location (latitude & longitude)',
                'Foto + caption LAPOR (simpan ke storage/app/public/reports & smoke_reports)',
                'CEK [Wilayah]',
                'JOGGING [Wilayah]',
                'LAPOR [Kategori] [Lokasi]',
            ],
        ]);
    }

    /**
     * POST /api/whatsapp/webhook — Ready for Baileys (wa-bot), Fonnte, & Meta WhatsApp Cloud API.
     * Exempt from CSRF via routes/api.php.
     */
    public function webhook(Request $request): JsonResponse
    {
        $extracted = $this->botService->extractIncomingPayload($request->all());

        $hasLocation = $extracted['latitude'] !== null && $extracted['longitude'] !== null;
        $hasImage = ! empty($extracted['image_base64']);

        if ($extracted['message'] === '' && ! $hasLocation && ! $hasImage) {
            return response()->json([
                'status' => 'ignored',
                'reply' => '',
                'reason' => 'Pesan kosong atau bukan event pesan teks/lokasi/foto.',
            ], 200);
        }

        $result = $this->botService->handleMessage($extracted['sender'], $extracted['message'], [
            'push_name' => $extracted['push_name'],
            'message_type' => $extracted['message_type'],
            'latitude' => $extracted['latitude'],
            'longitude' => $extracted['longitude'],
            'location_name' => $extracted['location_name'],
            'image_base64' => $extracted['image_base64'],
            'image_mime' => $extracted['image_mime'],
        ]);

        return response()->json([
            'status' => 'ok',
            'sender' => $result['sender'],
            'command' => $result['command'],
            'ai_powered' => $result['ai_powered'],
            'reply' => $result['reply'],
            'log_id' => $result['log_id'],
            'meta' => $result['meta'],
        ]);
    }
}
