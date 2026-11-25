<?php

namespace App\Services;

use App\Models\Region;
use App\Models\SmokeReport;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappBotService
{
    public function __construct(
        protected AirQualityService $airQualityService,
        protected GeminiAirQualityService $geminiService
    ) {}

    /**
     * Extract normalized incoming payload from Baileys (wa-bot), Fonnte, Meta Cloud API, or web simulator.
     */
    public function extractIncomingPayload(array $payload): array
    {
        // Meta WhatsApp Cloud API format
        if (! empty($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            $msg = $payload['entry'][0]['changes'][0]['value']['messages'][0];
            $type = $msg['type'] ?? 'text';
            $lat = isset($msg['location']['latitude']) ? (float) $msg['location']['latitude'] : null;
            $lng = isset($msg['location']['longitude']) ? (float) $msg['location']['longitude'] : null;

            return [
                'sender' => preg_replace('/[^0-9+]/', '', (string) ($msg['from'] ?? '6281234567890')) ?: '6281234567890',
                'push_name' => data_get($payload, 'entry.0.changes.0.value.contacts.0.profile.name', 'Warga via WA'),
                'message' => trim((string) ($msg['text']['body'] ?? $msg['image']['caption'] ?? $msg['location']['name'] ?? '')),
                'message_type' => $lat !== null && $lng !== null ? 'location' : ($type === 'image' ? 'image' : 'text'),
                'latitude' => $lat,
                'longitude' => $lng,
                'location_name' => $msg['location']['name'] ?? $msg['location']['address'] ?? null,
                'image_base64' => null,
                'image_mime' => null,
            ];
        }

        // Baileys (wa-bot) / Fonnte / Generic / Web Simulator format
        $sender = $payload['sender']
            ?? $payload['pengirim']
            ?? $payload['from']
            ?? $payload['phone']
            ?? '6281234567890';

        $message = $payload['message']
            ?? $payload['pesan']
            ?? $payload['caption']
            ?? $payload['text']
            ?? $payload['body']
            ?? '';

        $lat = isset($payload['latitude']) && is_numeric($payload['latitude'])
            ? (float) $payload['latitude']
            : (isset($payload['degreesLatitude']) && is_numeric($payload['degreesLatitude']) ? (float) $payload['degreesLatitude'] : null);

        $lng = isset($payload['longitude']) && is_numeric($payload['longitude'])
            ? (float) $payload['longitude']
            : (isset($payload['degreesLongitude']) && is_numeric($payload['degreesLongitude']) ? (float) $payload['degreesLongitude'] : null);

        $imageBase64 = isset($payload['image_base64']) && is_string($payload['image_base64']) && trim($payload['image_base64']) !== ''
            ? trim($payload['image_base64'])
            : null;

        $messageType = (string) ($payload['message_type'] ?? '');
        if ($messageType === '') {
            if ($imageBase64 !== null) {
                $messageType = 'image';
            } elseif ($lat !== null && $lng !== null) {
                $messageType = 'location';
            } else {
                $messageType = 'text';
            }
        }

        return [
            'sender' => preg_replace('/[^0-9+]/', '', (string) $sender) ?: '6281234567890',
            'push_name' => trim((string) ($payload['push_name'] ?? 'Warga via WA Bot')) ?: 'Warga via WA Bot',
            'message' => trim((string) $message),
            'message_type' => $messageType,
            'latitude' => $lat,
            'longitude' => $lng,
            'location_name' => isset($payload['location_name']) ? trim((string) $payload['location_name']) : null,
            'image_base64' => $imageBase64,
            'image_mime' => isset($payload['image_mime']) ? trim((string) $payload['image_mime']) : 'image/jpeg',
        ];
    }

    /**
     * Process an incoming message (text, Share Location, or image report),
     * generate reply (Gemini AI + rule-based fallback), and persist into whatsapp_logs.
     */
    public function handleMessage(string $senderPhone, string $incomingMessage, array $options = []): array
    {
        $cleanMessage = trim($incomingMessage);
        $messageType = $options['message_type'] ?? 'text';
        $pushName = $options['push_name'] ?? 'Warga via WA Bot';
        $latitude = isset($options['latitude']) && is_numeric($options['latitude']) ? (float) $options['latitude'] : null;
        $longitude = isset($options['longitude']) && is_numeric($options['longitude']) ? (float) $options['longitude'] : null;
        $locationName = $options['location_name'] ?? null;
        $imageBase64 = $options['image_base64'] ?? null;
        $imageMime = $options['image_mime'] ?? 'image/jpeg';

        $command = 'AI_CHAT';
        $reply = '';
        $extra = [];
        $aiPowered = false;

        $hasImage = ! empty($imageBase64) || $messageType === 'image';
        $isLaporCommand = (bool) preg_match('/^LAPOR(?:\s+(.+))?$/iu', $cleanMessage, $laporMatches);
        $isPhotoReport = $hasImage && (
            $isLaporCommand ||
            preg_match('/\blapor\b|sampah|asap|bakar|debu|knalpot|polusi/iu', $cleanMessage)
        );

        // 1. Photo + "lapor" OR explicit LAPOR command -> save to smoke_reports (+ optional photo in storage/app/public/reports)
        if ($isPhotoReport || $isLaporCommand) {
            $command = 'LAPOR';
            $args = $isLaporCommand ? trim($laporMatches[1] ?? '') : $cleanMessage;
            [$reply, $extra] = $this->handleLaporCommand(
                $senderPhone,
                $args,
                $pushName,
                $latitude,
                $longitude,
                $imageBase64,
                $imageMime
            );
        }
        // 2. Photo without "lapor" keyword -> gently guide user to add caption "LAPOR [Lokasi]"
        elseif ($hasImage) {
            $command = 'IMAGE_GUIDE';
            $reply = "Halo Kak! Foto sudah saya terima 📷\n\n"
                . "Kalau Kakak ingin *melaporkan titik asap* (bakar sampah, asap kendaraan, atau debu konstruksi), kirim ulang foto tersebut dengan caption:\n"
                . "• *LAPOR [Lokasi singkat]*\n"
                . "_Contoh: LAPOR pembakaran sampah dekat Simpang Lima Gumul Kediri_\n\n"
                . "Laporan akan otomatis masuk ke peta hotspot *NapasAman*: " . url('/lapor-asap');
        }
        // 3. Share Location message -> find nearest Kediri Raya region + fresh AQI + nearby smoke reports + Gemini AI (or fallback)
        elseif ($latitude !== null && $longitude !== null) {
            $command = 'LOCATION';
            [$reply, $extra, $aiPowered] = $this->handleLocationMessage(
                $senderPhone,
                $latitude,
                $longitude,
                $locationName,
                $cleanMessage
            );
        }
        // 4. Explicit CEK [Wilayah] command
        elseif (preg_match('/^CEK(?:\s+(.+))?$/iu', $cleanMessage, $matches)) {
            $command = 'CEK';
            $query = trim($matches[1] ?? '');
            [$reply, $extra] = $this->handleCekCommand($query);
        }
        // 5. Explicit JOGGING [Wilayah] command
        elseif (preg_match('/^JOGGING(?:\s+(.+))?$/iu', $cleanMessage, $matches)) {
            $command = 'JOGGING';
            $query = trim($matches[1] ?? '');
            [$reply, $extra] = $this->handleJoggingCommand($query);
        }
        // 6. Free-form Q&A -> Google Gemini AI grounded on real-time 7-region MySQL data, with rule-based fallback
        else {
            [$command, $reply, $extra, $aiPowered] = $this->handleFreeFormMessage($senderPhone, $cleanMessage);
        }

        // Persist conversation log to MySQL whatsapp_logs
        $logMessage = $cleanMessage;
        if ($latitude !== null && $longitude !== null && ! str_contains($logMessage, (string) round($latitude, 2))) {
            $logMessage = trim("[Share Location: {$latitude}, {$longitude}] {$logMessage}");
        } elseif ($hasImage) {
            $logMessage = trim("[Foto] {$logMessage}");
        }

        $log = WhatsappLog::create([
            'sender_phone' => $senderPhone,
            'incoming_message' => $logMessage ?: '(kosong)',
            'bot_reply' => $reply,
            'command' => $command,
        ]);

        return [
            'status' => 'ok',
            'command' => $command,
            'ai_powered' => $aiPowered,
            'sender' => $senderPhone,
            'incoming_message' => $logMessage,
            'reply' => $reply,
            'log_id' => $log->id,
            'timestamp' => $log->created_at->format('H:i'),
            'meta' => $extra,
        ];
    }

    /**
     * Handle Share Location message using nearest Kediri Raya region + Open-Meteo freshness (> 1 jam) + Gemini AI + fallback.
     */
    protected function handleLocationMessage(
        string $senderPhone,
        float $lat,
        float $lng,
        ?string $locationName,
        string $caption
    ): array {
        $regions = $this->airQualityService->getRegionsWithLatestAirQuality(false, 60);
        $nearestInfo = $this->airQualityService->findNearestRegion($lat, $lng, $regions);
        $nearestRegion = $nearestInfo['region'];
        $withinScope = $nearestInfo['within_kediri_raya'];

        $nearbyReports = $withinScope
            ? $this->airQualityService->getNearbyActiveSmokeReports($lat, $lng, 20.0, 4)
            : collect();

        $locationContext = [
            'lat' => $lat,
            'lng' => $lng,
            'region' => $nearestRegion,
            'distance_km' => $nearestInfo['distance_km'],
            'within_kediri_raya' => $withinScope,
        ];

        $userPrompt = $caption !== ''
            ? $caption
            : ($locationName ? "[Share Location: {$locationName}]" : '[Share Location]');

        $aiReply = $this->geminiService->generateReply(
            $senderPhone,
            $userPrompt,
            $regions,
            $locationContext,
            $nearbyReports
        );

        if ($aiReply !== null) {
            return [
                $aiReply,
                [
                    'region' => $nearestRegion?->name,
                    'distance_km' => $nearestInfo['distance_km'],
                    'within_kediri_raya' => $withinScope,
                    'nearby_reports' => $nearbyReports->count(),
                ],
                true,
            ];
        }

        // Rule-based fallback when Gemini API key is empty or unavailable
        if (! $withinScope) {
            $reply = "Maaf ya Kak 🙏 Lokasi yang dikirim ({$lat}, {$lng}) terdeteksi di luar cakupan *7 Wilayah Eks-Karesidenan Kediri*.\n\n"
                . "Saat ini *NapasAman* berfokus pada pilot area: Kota/Kab. Kediri, Tulungagung, Kota/Kab. Blitar, Nganjuk, dan Trenggalek.\n"
                . "Kakak tetap bisa cek kualitas udara daerah tersebut dengan mengetik misal: *CEK KEDIRI* atau *CEK TULUNGAGUNG* 🌿";

            return [$reply, ['within_kediri_raya' => false], false];
        }

        $aq = $nearestRegion->latestAirQuality;
        $emoji = $this->aqiEmoji((int) $aq->aqi);
        $hourly = $this->airQualityService->getHourlyTrend($nearestRegion);
        $jog = $this->airQualityService->getBestJoggingWindow($hourly);
        $recs = $this->airQualityService->getSegmentRecommendations((int) $aq->aqi, (float) $aq->pm25, $jog);

        $hotspotNote = $nearbyReports->isEmpty()
            ? 'Tidak ada laporan titik asap aktif di sekitar lokasimu.'
            : "Ada *{$nearbyReports->count()} titik asap aktif* di sekitar wilayah ini (terdekat: {$nearbyReports->first()->category}, ~{$nearbyReports->first()->distance_km} km).";

        $reply = "Lokasi terdeteksi di sekitar *{$nearestRegion->name}* 📍\n"
            . "{$emoji} *US AQI:* {$aq->aqi} (*{$aq->category}*)\n"
            . "• *PM2.5:* {$aq->pm25} µg/m³ | *PM10:* {$aq->pm10} µg/m³\n"
            . "• *Titik Asap Aktif:* {$hotspotNote}\n\n"
            . "*Rekomendasi Harian:*\n"
            . "• *Komuter:* {$recs['komuter']['headline']}\n"
            . "• *Keluarga (Anak/Lansia):* {$recs['keluarga']['headline']}\n"
            . "• *Pelari:* {$recs['pelari']['headline']}\n\n"
            . "Lihat peta rute rendah polusi di sini: 🔗 " . url('/rute-bersih');

        return [
            $reply,
            [
                'region' => $nearestRegion->name,
                'distance_km' => $nearestInfo['distance_km'],
                'within_kediri_raya' => true,
                'nearby_reports' => $nearbyReports->count(),
            ],
            false,
        ];
    }

    /**
     * Handle free-form conversational questions using Gemini AI + real-time 7-region MySQL data,
     * with reliable rule-based fallback if API key is empty or rate-limited.
     */
    protected function handleFreeFormMessage(string $senderPhone, string $cleanMessage): array
    {
        $upper = mb_strtoupper($cleanMessage);
        if ($cleanMessage === '' || in_array($upper, ['MENU', 'HELP', 'BANTUAN'], true)) {
            return ['MENU', $this->getHelpMessage(), ['mode' => 'menu'], false];
        }

        $regions = $this->airQualityService->getRegionsWithLatestAirQuality(false, 60);

        // Try Gemini AI first for natural conversational Q&A
        $aiReply = $this->geminiService->generateReply($senderPhone, $cleanMessage, $regions);
        if ($aiReply !== null) {
            return ['AI_CHAT', $aiReply, ['provider' => 'gemini'], true];
        }

        // --- Rule-Based Fallback (never fails even if Gemini API key is empty / quota limit) ---
        $lower = mb_strtolower($cleanMessage);

        // 1. Guardrail: cities outside Eks-Karesidenan Kediri
        $outsideCities = ['jakarta', 'surabaya', 'malang', 'bandung', 'semarang', 'jogja', 'yogyakarta', 'solo', 'bali', 'denpasar', 'sidoarjo', 'gresik', 'madiun', 'jombang', 'mojokerto', 'banyuwangi'];
        foreach ($outsideCities as $city) {
            if (preg_match('/\b' . preg_quote($city, '/') . '\b/u', $lower)) {
                return [
                    'OUT_OF_SCOPE',
                    "Maaf ya Kak 🙏 Saat ini *NapasAman* masih berfokus pada layanan pilot di 7 wilayah *Eks-Karesidenan Kediri* (Kota/Kab. Kediri, Tulungagung, Kota/Kab. Blitar, Nganjuk, dan Trenggalek), sebelum nantinya dikembangkan ke seluruh Jawa Timur.\n\n"
                    . "Kalau Kakak ingin cek udara atau rute bersih di salah satu dari 7 daerah tersebut, langsung ketik saja nama kotanya ya! 🌿",
                    ['out_of_scope_city' => $city],
                    false,
                ];
            }
        }

        // 2. Greeting
        if (preg_match('/^(halo|hai|hello|hi|assalamualaikum|selamat\s+(pagi|siang|sore|malam)|p|mulai|ini\s+bot\s+apa)/iu', $cleanMessage)) {
            return ['MENU', $this->getHelpMessage(), ['fallback' => 'greeting'], false];
        }

        // 3. Free-form jogging / exercise query
        $matchedRegion = $this->findRegionByText($cleanMessage, $regions);
        if (preg_match('/jogging|lari|olahraga|sepeda|gowes/iu', $cleanMessage)) {
            [$reply, $extra] = $this->handleJoggingCommand($matchedRegion?->name ?? 'Kota Kediri');
            return ['JOGGING', $reply, array_merge($extra, ['fallback' => true]), false];
        }

        // 4. Free-form air quality query mentioning a Kediri Raya region
        if ($matchedRegion) {
            [$reply, $extra] = $this->handleCekCommand($matchedRegion->name);
            return ['CEK', $reply, array_merge($extra, ['fallback' => true]), false];
        }

        // 5. General air quality question without region name -> show 7-region summary
        if (preg_match('/udara|aqi|pm2|polusi|asap|sehat|masker|rute/iu', $cleanMessage)) {
            [$reply, $extra] = $this->handleCekCommand('');
            return ['CEK', $reply, array_merge($extra, ['fallback' => true]), false];
        }

        // 6. Polite off-topic fallback
        return [
            'UNKNOWN',
            "Wah, maaf Kak, saya khusus bantu urusan _kualitas udara bersih, rute rendah polusi, dan pelaporan asap_ di wilayah *Eks-Karesidenan Kediri* saja 😅\n\n"
            . "Mau saya cekkan kondisi udara atau jam jogging paling aman di daerahmu hari ini? Coba ketik *CEK KEDIRI* atau kirim *Share Location* ya! 🌿",
            ['fallback' => 'off_topic'],
            false,
        ];
    }

    protected function handleCekCommand(string $query): array
    {
        $regions = $this->airQualityService->getRegionsWithLatestAirQuality(false, 60);

        if ($query === '') {
            $lines = ['*LAPORAN KUALITAS UDARA EKS-KARESIDENAN KEDIRI*', "Pantauan real-time NapasAman:\n"];
            foreach ($regions as $region) {
                $aq = $region->latestAirQuality;
                $emoji = $this->aqiEmoji((int) $aq->aqi);
                $lines[] = "{$emoji} *{$region->name}*: AQI {$aq->aqi} ({$aq->category}) • PM2.5 {$aq->pm25} µg/m³";
            }
            $lines[] = "\nKetik *CEK [Nama Wilayah]* untuk detail, misal: *CEK KEDIRI* atau *CEK TULUNGAGUNG*.";
            $lines[] = '🗺️ Rute Bersih: ' . url('/rute-bersih');

            return [implode("\n", $lines), ['mode' => 'all_regions']];
        }

        $region = $this->findRegionByText($query, $regions);

        if (! $region) {
            return [
                "Maaf ya Kak 🙏 Wilayah *\"{$query}\"* di luar cakupan *Eks-Karesidenan Kediri*.\n\n"
                . "Wilayah yang tersedia:\n"
                . "• Kota Kediri / Kab. Kediri\n"
                . "• Kab. Tulungagung\n"
                . "• Kota Blitar / Kab. Blitar\n"
                . "• Kab. Nganjuk\n"
                . "• Kab. Trenggalek\n\n"
                . 'Contoh: *CEK KEDIRI* atau *CEK TULUNGAGUNG*',
                ['found' => false],
            ];
        }

        $aq = $region->latestAirQuality;
        $emoji = $this->aqiEmoji((int) $aq->aqi);
        $exposure = $this->airQualityService->calculateExposure((float) $aq->pm25, 'motor', 60);
        $hourly = $this->airQualityService->getHourlyTrend($region);
        $jog = $this->airQualityService->getBestJoggingWindow($hourly);
        $recs = $this->airQualityService->getSegmentRecommendations((int) $aq->aqi, (float) $aq->pm25, $jog);

        $activeReports = SmokeReport::where('region_id', $region->id)
            ->whereIn('status', ['dilaporkan', 'diverifikasi'])
            ->count();

        $routeUrl = url('/rute-bersih');
        $calcUrl = url('/kalkulator-paparan');

        $reply = "{$emoji} *Kualitas Udara {$region->name}*\n"
            . "━━━━━━━━━━━━━━━━━━\n"
            . "📊 *AQI:* {$aq->aqi} (*{$aq->category}*)\n"
            . "🌫️ *PM2.5:* {$aq->pm25} µg/m³ | *PM10:* {$aq->pm10} µg/m³ | *NO2:* {$aq->no2} µg/m³\n"
            . "🚬 *Analogi Paparan:* 1 jam naik motor saat ini ≈ *{$exposure['cigarettes']} batang rokok* ({$exposure['total_inhaled_ug']} µg PM2.5 terhirup)\n"
            . "🔥 *Laporan Asap Aktif:* {$activeReports} titik di wilayah ini\n\n"
            . "💡 *Saran Aktivitas:*\n"
            . "• Komuter: {$recs['komuter']['headline']}\n"
            . "• Keluarga: {$recs['keluarga']['headline']}\n"
            . "• Pelari: {$recs['pelari']['headline']}\n\n"
            . "🗺️ Cari jalur rendah polusi: {$routeUrl}\n"
            . "🧮 Hitung paparan pribadi: {$calcUrl}\n"
            . '_NapasAman — Cek Kualitas Udara. Hidup Lebih Sehat._';

        return [$reply, ['region' => $region->name, 'aqi' => $aq->aqi]];
    }

    protected function handleJoggingCommand(string $query): array
    {
        $regions = $this->airQualityService->getRegionsWithLatestAirQuality(false, 60);
        $region = $query !== '' ? $this->findRegionByText($query, $regions) : $regions->first();

        if (! $region) {
            return [
                "Wilayah *\"{$query}\"* tidak ditemukan di Eks-Karesidenan Kediri.\n"
                . 'Coba ketik: *JOGGING KEDIRI*, *JOGGING BLITAR*, *JOGGING TULUNGAGUNG*, *JOGGING NGANJUK*, atau *JOGGING TRENGGALEK*.',
                ['found' => false],
            ];
        }

        $aq = $region->latestAirQuality;
        $hourly = $this->airQualityService->getHourlyTrend($region);
        $jog = $this->airQualityService->getBestJoggingWindow($hourly);

        $reply = "🏃 *Rekomendasi Jam Jogging — {$region->name}*\n"
            . "━━━━━━━━━━━━━━━━━━\n"
            . "✅ *Waktu Paling Bersih Hari Ini:*\n"
            . "🌅 Pagi: *{$jog['morning']['hour']}* (PM2.5 ~{$jog['morning']['pm25']} µg/m³)\n"
            . "🌇 Sore: *{$jog['afternoon']['hour']}* (PM2.5 ~{$jog['afternoon']['pm25']} µg/m³)\n\n"
            . "⭐ *Pilihan Terbaik:* *{$jog['primary']['hour']}*\n"
            . "📍 *Kondisi Saat Ini:* AQI {$aq->aqi} ({$aq->category}) • PM2.5 {$aq->pm25} µg/m³\n\n"
            . "🛡️ *Tips Lari Sehat:*\n"
            . "• Pilih rute taman/hijau jauh dari jalan arteri padat.\n"
            . '• Cek peta titik asap sebelum mulai berlari: ' . url('/rute-bersih') . "\n"
            . '_Tetap sehat bersama Sobat Napas!_';

        return [$reply, ['region' => $region->name, 'best_window' => $jog['primary']['hour']]];
    }

    protected function handleLaporCommand(
        string $senderPhone,
        string $args,
        string $pushName = 'Warga via WA Bot',
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $imageBase64 = null,
        string $imageMime = 'image/jpeg'
    ): array {
        if ($args === '' && empty($imageBase64)) {
            return [
                "Format pelaporan titik asap:\n"
                . "*LAPOR [Kategori] [Lokasi & Keterangan]*\n\n"
                . "Atau kirim *Foto + caption LAPOR [Lokasi]* langsung di chat ini!\n\n"
                . "Kategori yang dikenali:\n"
                . "• *SAMPAH* (Pembakaran Sampah Terbuka)\n"
                . "• *KENDARAAN* (Asap Kendaraan Pekat)\n"
                . "• *DEBU* (Debu Konstruksi)\n\n"
                . "Contoh:\n*LAPOR SAMPAH Pembakaran sampah di dekat Simpang Lima Gumul Kediri*",
                ['created' => false],
            ];
        }

        // Remove leading "LAPOR" word if caption was passed raw (e.g. from photo caption)
        $cleanArgs = trim((string) preg_replace('/^lapor\b[\s:,-]*/iu', '', $args));
        $parts = preg_split('/\s+/', $cleanArgs, 2);
        $firstToken = mb_strtolower($parts[0] ?? '');
        $restText = trim($parts[1] ?? $cleanArgs);

        $lowerAll = mb_strtolower($cleanArgs);
        $category = match (true) {
            str_contains($firstToken, 'sampah') || str_contains($firstToken, 'bakar') || str_contains($lowerAll, 'sampah') || str_contains($lowerAll, 'bakar') => 'Pembakaran Sampah Terbuka',
            str_contains($firstToken, 'kendaraan') || str_contains($firstToken, 'knalpot') || str_contains($firstToken, 'macet') || str_contains($lowerAll, 'kendaraan') || str_contains($lowerAll, 'knalpot') => 'Asap Kendaraan Pekat',
            str_contains($firstToken, 'debu') || str_contains($firstToken, 'konstruksi') || str_contains($firstToken, 'proyek') || str_contains($lowerAll, 'debu') || str_contains($lowerAll, 'konstruksi') => 'Debu Konstruksi',
            default => 'Pembakaran Sampah Terbuka',
        };

        if (! preg_match('/^(sampah|bakar|kendaraan|knalpot|macet|debu|konstruksi|proyek)$/i', $firstToken)) {
            $restText = $cleanArgs;
        }

        if ($restText === '') {
            $restText = 'Laporan titik asap dengan foto bukti melalui WhatsApp.';
        }

        $regions = Region::where('is_active', true)->get();

        // Resolve region: prioritize text mention, else GPS nearest if within Kediri Raya, else default Kota Kediri
        $region = $this->findRegionByText($restText, $regions);
        if (! $region && $latitude !== null && $longitude !== null && $this->airQualityService->isWithinKediriRaya($latitude, $longitude)) {
            $nearest = $this->airQualityService->findNearestRegion($latitude, $longitude, $regions);
            $region = $nearest['region'];
        }
        $region = $region ?? $regions->first();

        // Coordinates: use real GPS if within Kediri Raya bounds, else slight offset from region center
        if ($latitude !== null && $longitude !== null && $this->airQualityService->isWithinKediriRaya($latitude, $longitude)) {
            $finalLat = round($latitude, 6);
            $finalLng = round($longitude, 6);
        } else {
            $finalLat = round($region->latitude + (mt_rand(-120, 120) / 10000), 6);
            $finalLng = round($region->longitude + (mt_rand(-120, 120) / 10000), 6);
        }

        // Save uploaded base64 photo to storage/app/public/reports/
        $photoPath = null;
        if (! empty($imageBase64)) {
            $photoPath = $this->storeBase64ReportPhoto($imageBase64, $imageMime);
        }

        $report = SmokeReport::create([
            'region_id' => $region->id,
            'reporter_name' => $pushName ?: 'Warga via WA Bot',
            'reporter_phone' => $senderPhone,
            'category' => $category,
            'description' => "[Via WA Bot] {$restText}",
            'latitude' => $finalLat,
            'longitude' => $finalLng,
            'photo_path' => $photoPath,
            'status' => 'dilaporkan',
        ]);

        $photoLine = $photoPath ? "📷 *Foto Bukti:* Tersimpan\n" : '';

        $reply = "Terima kasih banyak atas laporannya, Kak! 🙏\n\n"
            . "Laporan titik asapmu sudah *berhasil tercatat* di sistem *NapasAman*:\n"
            . "• *ID Laporan:* #NA-{$report->id}\n"
            . "• *Kategori:* {$report->category}\n"
            . "• *Wilayah:* {$region->name}\n"
            . "• *Keterangan:* {$restText}\n"
            . $photoLine
            . "• *Status:* Dilaporkan _(aktif di peta hotspot)_\n\n"
            . "Pengguna lain kini mendapat peringatan agar menghindari jalur tersebut, dan data ini menjadi bahan edukasi persuasif bersama RT/RW serta Bank Sampah setempat.\n"
            . '🔗 Pantau di peta: ' . url('/lapor-asap') . "\n"
            . '🗺️ Rute Bersih: ' . url('/rute-bersih');

        return [
            $reply,
            [
                'created' => true,
                'report_id' => $report->id,
                'region' => $region->name,
                'photo_path' => $photoPath,
            ],
        ];
    }

    /**
     * Decode base64 image from WhatsApp Baileys and store into storage/app/public/reports/.
     */
    protected function storeBase64ReportPhoto(string $base64, string $mime = 'image/jpeg'): ?string
    {
        try {
            // Strip optional data URI prefix (data:image/jpeg;base64,...)
            if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/s', $base64, $m)) {
                $mime = $m[1];
                $base64 = $m[2];
            }

            $binary = base64_decode($base64, true);
            if ($binary === false || strlen($binary) === 0 || strlen($binary) > 6 * 1024 * 1024) {
                return null;
            }

            $ext = match (strtolower($mime)) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            $filename = 'reports/wa_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(6)) . '.' . $ext;
            Storage::disk('public')->put($filename, $binary);

            return $filename;
        } catch (\Throwable $e) {
            Log::warning('Gagal menyimpan foto laporan WA: ' . $e->getMessage());
            return null;
        }
    }

    protected function findRegionByText(string $text, $regions = null): ?Region
    {
        $regions = $regions ?? Region::where('is_active', true)->get();
        $needle = mb_strtolower(trim($text));

        foreach ($regions as $region) {
            $nameLower = mb_strtolower($region->name);
            if (str_contains($needle, $nameLower) || ($needle !== '' && str_contains($nameLower, $needle))) {
                return $region;
            }
        }

        $aliases = [
            'kabupaten-kediri' => ['kab kediri', 'kab. kediri', 'gumul', 'slg', 'pare', 'ngasem', 'gurah', 'papar', 'kras', 'wates'],
            'kota-kediri' => ['kota kediri', 'kediri', 'mojoroto', 'pesantren', 'alun-alun kediri', 'brantas', 'dhoho'],
            'kabupaten-tulungagung' => ['tulungagung', 'boyolangu', 'ngunut', 'kalidawir', 'kedungwaru'],
            'kota-blitar' => ['kota blitar', 'bung karno', 'kepanjenkidul', 'sananwetan', 'sukorejo'],
            'kabupaten-blitar' => ['kab blitar', 'kab. blitar', 'kanigoro', 'wlingi', 'srengat', 'garum', 'blitar'],
            'kabupaten-nganjuk' => ['nganjuk', 'kertosono', 'warujayeng', 'tanjunganom', 'pace', 'berbek'],
            'kabupaten-trenggalek' => ['trenggalek', 'durenan', 'pogalan', 'karangan', 'watulimo', 'prigi'],
        ];

        foreach ($aliases as $slug => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($needle, $kw)) {
                    $found = $regions->firstWhere('slug', $slug);
                    if ($found) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }

    protected function aqiEmoji(int $aqi): string
    {
        return match (true) {
            $aqi <= 50 => '🟢',
            $aqi <= 100 => '🟡',
            $aqi <= 150 => '🟠',
            default => '🔴',
        };
    }

    protected function getHelpMessage(): string
    {
        return "Halo Kak! 👋 Saya *Sobat Napas* dari *NapasAman*, asisten kualitas udara khusus *Wilayah Eks-Karesidenan Kediri* (Kediri, Tulungagung, Blitar, Nganjuk, dan Trenggalek).\n\n"
            . "Kamu bisa langsung chat santai (ditenagai AI) atau pakai cara cepat ini:\n"
            . "• Kirim *Share Location* 📍 untuk cek PM2.5 & titik asap di lokasimu\n"
            . "• Tanya bebas, misal: _\"Aman gak jogging sore ini di Kediri?\"_\n"
            . "• *CEK [Wilayah]* — misal _CEK TULUNGAGUNG_\n"
            . "• *JOGGING [Wilayah]* — misal _JOGGING BLITAR_\n"
            . "• Kirim *Foto + caption LAPOR [Lokasi]* — lapor titik bakar sampah/asap pekat\n\n"
            . "Mau cek kualitas udara di daerah mana hari ini, Kak? 🌿";
    }
}
