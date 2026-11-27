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
        $botNumber = preg_replace('/[^0-9]/', '', (string) config('services.whatsapp.bot_number', '62895366866722')) ?: '62895366866722';
        $formattedNumber = '+62 895-3668-66722';
        if ($botNumber !== '62895366866722') {
            $formattedNumber = '+' . $botNumber;
        }

        $defaultInitialMessage = 'Halo Sobat Napas, saya ingin cek kualitas udara di wilayah Eks-Karesidenan Kediri';

        $quickLinks = [
            [
                'title' => 'Mulai Chat & Menu Utama',
                'desc' => 'Sapaan awal otomatis untuk melihat panduan lengkap & kondisi udara Kediri Raya.',
                'text' => $defaultInitialMessage,
                'badge' => 'Pesan Awal Otomatis',
                'color' => 'emerald',
            ],
            [
                'title' => 'Cek Kualitas Udara Kota Kediri',
                'desc' => 'Langsung dapatkan AQI, PM2.5, analogi rokok, & tautan rute bersih Kota Kediri.',
                'text' => 'CEK KEDIRI',
                'badge' => 'Cepat',
                'color' => 'sky',
            ],
            [
                'title' => 'Cek Udara Kabupaten Tulungagung',
                'desc' => 'Pantau PM2.5 dan rekomendasi aktivitas harian warga Tulungagung.',
                'text' => 'CEK TULUNGAGUNG',
                'badge' => 'Cepat',
                'color' => 'sky',
            ],
            [
                'title' => 'Tanya Jam Jogging (Gemini AI)',
                'desc' => 'Tanya bebas ke AI Sobat Napas untuk saran jam olahraga paling bersih hari ini.',
                'text' => 'Aman gak jogging sore ini di Kediri?',
                'badge' => 'Gemini AI',
                'color' => 'navy',
            ],
            [
                'title' => 'Cek Jam Lari di Blitar',
                'desc' => 'Rekomendasi jendela waktu pagi & sore paling aman untuk lari di Blitar.',
                'text' => 'JOGGING BLITAR',
                'badge' => 'Pelari',
                'color' => 'emerald',
            ],
            [
                'title' => 'Lapor Titik Asap / Bakar Sampah',
                'desc' => 'Kirim laporan warga (bisa sertakan foto) agar masuk ke peta hotspot NapasAman.',
                'text' => 'LAPOR SAMPAH Dekat Simpang Lima Gumul Kediri',
                'badge' => 'Crowdsourcing',
                'color' => 'orange',
            ],
        ];

        $totalInteractions = WhatsappLog::count();
        $uniqueUsers = WhatsappLog::distinct('sender_phone')->count('sender_phone');

        return view('bot-wa', [
            'botNumber' => $botNumber,
            'formattedNumber' => $formattedNumber,
            'defaultInitialMessage' => $defaultInitialMessage,
            'primaryWaUrl' => 'https://wa.me/' . $botNumber . '?text=' . rawurlencode($defaultInitialMessage),
            'quickLinks' => $quickLinks,
            'totalInteractions' => $totalInteractions,
            'uniqueUsers' => $uniqueUsers,
            'webhookUrl' => url('/api/whatsapp/webhook'),
            'geminiEnabled' => $this->geminiService->isConfigured(),
            'geminiModel' => config('services.gemini.model', 'gemini-2.5-flash'),
        ]);
    }

    /**
     * GET /api/whatsapp/webhook — Meta WhatsApp Cloud API verification & health check.
     * Validates hub.verify_token before echoing hub.challenge.
     */
    public function verifyWebhook(Request $request): Response|JsonResponse
    {
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');
        $verifyToken = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $expectedToken = (string) config('services.whatsapp.verify_token', '');

        if ($challenge !== null) {
            if ($expectedToken === '' || ! is_string($verifyToken) || ! hash_equals($expectedToken, $verifyToken)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Verify token tidak valid.',
                ], 403);
            }

            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json([
            'status' => 'ok',
            'service' => 'NapasAman Sobat Napas WhatsApp Webhook (Baileys + Gemini AI)',
            'bot_number' => '+' . config('services.whatsapp.bot_number', '62895366866722'),
            'gemini_configured' => $this->geminiService->isConfigured(),
            'gemini_model' => config('services.gemini.model', 'gemini-2.5-flash'),
            'webhook_protected' => trim((string) config('services.whatsapp.webhook_secret', '')) !== '',
            'region_scope' => 'Eks-Karesidenan Kediri (7 Wilayah)',
        ]);
    }

    /**
     * POST /api/whatsapp/webhook — Ready for Baileys (wa-bot), Fonnte, & Meta WhatsApp Cloud API.
     * Protected by X-Webhook-Secret / Fonnte Authorization / Meta X-Hub-Signature-256.
     */
    public function webhook(Request $request): JsonResponse
    {
        if (! $this->isAuthorizedWebhook($request)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized webhook request: secret atau signature tidak valid.',
            ], 401);
        }

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

    /**
     * Verify shared secret (Baileys / Fonnte) or HMAC-SHA256 signature (Meta WhatsApp Cloud API).
     */
    protected function isAuthorizedWebhook(Request $request): bool
    {
        $configuredSecret = trim((string) config('services.whatsapp.webhook_secret', ''));
        $metaAppSecret = trim((string) config('services.whatsapp.meta_app_secret', ''));

        // If neither secret is configured, allow (local dev convenience)
        if ($configuredSecret === '' && $metaAppSecret === '') {
            return true;
        }

        // 1. Check Baileys / custom X-Webhook-Secret header
        $providedSecret = (string) ($request->header('X-Webhook-Secret') ?? '');
        if ($configuredSecret !== '' && $providedSecret !== '' && hash_equals($configuredSecret, $providedSecret)) {
            return true;
        }

        // 2. Check Fonnte Authorization / Bearer header
        $authHeader = trim((string) ($request->header('Authorization') ?? ''));
        $bearerToken = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : $authHeader;
        if ($configuredSecret !== '' && $bearerToken !== '' && hash_equals($configuredSecret, $bearerToken)) {
            return true;
        }

        // 3. Check Meta WhatsApp Cloud API X-Hub-Signature-256 HMAC
        $metaSig = (string) ($request->header('X-Hub-Signature-256') ?? '');
        $hmacSecret = $metaAppSecret !== '' ? $metaAppSecret : $configuredSecret;
        if ($hmacSecret !== '' && str_starts_with($metaSig, 'sha256=')) {
            $expectedSig = 'sha256=' . hash_hmac('sha256', $request->getContent(), $hmacSecret);
            if (hash_equals($expectedSig, $metaSig)) {
                return true;
            }
        }

        return false;
    }
}
