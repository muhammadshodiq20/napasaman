<?php

namespace App\Services;

use App\Models\Region;
use App\Models\SmokeReport;
use App\Models\WhatsappLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAirQualityService
{
    public function __construct(
        protected AirQualityService $airQualityService
    ) {}

    public function isConfigured(): bool
    {
        return trim((string) config('services.gemini.api_key')) !== '';
    }

    /**
     * Generate a natural Indonesian WhatsApp reply powered by Google Gemini API
     * grounded on real-time MySQL air quality & active smoke report data.
     *
     * Returns null when API key is missing, rate-limited, or unreachable so the caller
     * can transparently fall back to deterministic rule-based replies.
     */
    public function generateReply(
        string $senderPhone,
        string $userMessage,
        Collection $regions,
        ?array $locationInfo = null,
        ?Collection $nearbyReports = null
    ): ?string {
        $apiKey = trim((string) config('services.gemini.api_key'));
        if ($apiKey === '') {
            return null;
        }

        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $appUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');

        $systemPrompt = $this->buildSystemInstruction($appUrl);
        $contextBlock = $this->buildDynamicContext($regions, $userMessage, $appUrl, $locationInfo, $nearbyReports);

        // Build multi-turn contents: Few-Shot Examples + Recent User History (3-5 turns) + Current Context/Message
        $contents = $this->buildFewShotContents($appUrl);

        $recentHistory = WhatsappLog::where('sender_phone', $senderPhone)
            ->latest('id')
            ->limit(4)
            ->get()
            ->reverse();

        foreach ($recentHistory as $log) {
            if (trim((string) $log->incoming_message) === '' || trim((string) $log->bot_reply) === '') {
                continue;
            }
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => mb_substr($log->incoming_message, 0, 400)]],
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => mb_substr($log->bot_reply, 0, 700)]],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $contextBlock]],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.35,
                'maxOutputTokens' => 500,
            ],
        ];

        $modelsToTry = array_values(array_unique([
            $model,
            'gemini-3.8-flash',
            'gemini-flash-latest',
            'gemini-3.5-flash-lite',
        ]));

        $base = rtrim((string) config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');

        foreach ($modelsToTry as $candidateModel) {
            $endpointUrl = "{$base}/{$candidateModel}:generateContent?key=" . urlencode($apiKey);

            try {
                $response = Http::timeout(15)
                    ->connectTimeout(4)
                    ->acceptJson()
                    ->post($endpointUrl, $payload);

                if ($response->successful()) {
                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    if (is_string($text) && trim($text) !== '') {
                        return $this->sanitizeForWhatsApp(trim($text));
                    }
                }

                // Stop retrying on 429 quota limit
                if ($response->status() === 429) {
                    Log::warning('Gemini API rate limit (429), falling back to rule-based reply.');
                    return null;
                }

                Log::warning('Gemini API non-200 response', [
                    'model' => $candidateModel,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 260),
                ]);

                // Try fallback model aliases when configured model returns 404 (deprecated) or 503 (temporary spike)
                if (! in_array($response->status(), [404, 503], true)) {
                    return null;
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini API request failed: ' . $e->getMessage());
                return null;
            }
        }

        return null;
    }

    /**
     * Bagian 1 & Bagian 3: System Instruction & Knowledge Base Acuan AI ("Sobat Napas").
     */
    protected function buildSystemInstruction(string $appUrl): string
    {
        return <<<PROMPT
Kamu adalah *Sobat Napas*, asisten WhatsApp resmi dari *NapasAman* ("Cek Kualitas Udara. Hidup Lebih Sehat.") yang dikembangkan oleh Tim JosJiz AG untuk membantu warga di *Wilayah Eks-Karesidenan Kediri* (Kota Kediri, Kabupaten Kediri, Kabupaten Tulungagung, Kota Blitar, Kabupaten Blitar, Kabupaten Nganjuk, dan Kabupaten Trenggalek).

1. Kepribadian & Gaya Bahasa:
- Gunakan Bahasa Indonesia yang ramah, santai tapi sopan, hangat, dan mudah dipahami oleh semua kalangan (mahasiswa, komuter, orang tua/lansia, hingga pelari).
- Sapa pengguna secara natural (boleh memakai "Halo", "Kak", atau "Sobat"). Hindari bahasa yang terlalu kaku atau istilah teknis tanpa penjelasan.
- Gunakan format khusus WhatsApp: *teks tebal* untuk judul/angka penting, _miring_ untuk catatan kecil, dan bullet • agar nyaman dibaca di layar HP.
- JANGAN gunakan heading Markdown (#, ##, ###), bold ganda (**), atau tabel Markdown karena tidak rapi di WhatsApp.
- Jaga jawaban tetap ringkas dan padat (maksimal 2 paragraf singkat + poin bullet, idealnya 5-12 baris, maksimal ~150 kata).

2. Tugas Utama Kamu:
- Cek Kualitas Udara & Terjemahkan PM2.5: Selalu gunakan angka AQI dan PM2.5 ASLI dari [KONTEKS REAL-TIME SISTEM] yang diberikan. JANGAN pernah mengarang angka kualitas udara sendiri. Jelaskan kategori (Baik, Sedang, Tidak Sehat bagi Kelompok Sensitif, atau Tidak Sehat) serta analogi paparan (~22 µg/m³ PM2.5 selama 24 jam ≈ 1 batang rokok; untuk 1-2 jam aktivitas luar gunakan estimasi ringan).
- Beri Rekomendasi Sesuai Segmen Pengguna berdasarkan Standar Panduan Aksi:
  • AQI 0-50 (Baik): Komuter/Mahasiswa aman beraktivitas normal; Keluarga aman bermain & buka ventilasi; Pelari waktu ideal olahraga luar.
  • AQI 51-100 (Sedang): Komuter/Mahasiswa aman namun hindari jalur macet padat knalpot; Keluarga kelompok sensitif kurangi paparan lama di pinggir jalan; Pelari aman di jalur taman/rindang & hindari jam sibuk.
  • AQI 101-150 (Tidak Sehat bagi Kelompok Sensitif): Komuter/Mahasiswa pakai masker saat naik motor & cek rute terbersih; Keluarga (anak/lansia/ibu hamil) kurangi aktivitas luar; Pelari kurangi intensitas/durasi atau geser ke pagi lebih awal.
  • AQI 151-200+ (Tidak Sehat): Komuter/Mahasiswa wajib masker N95/KF94 & hindari rute macet/titik asap; Keluarga tetap di dalam ruangan & tutup jendela; Pelari tunda jogging luar ruangan, ganti olahraga indoor.
- Arahkan ke Web App PWA NapasAman: Sertakan tautan relevan ke Peta Rute Bersih ({$appUrl}/rute-bersih), Kalkulator Paparan ({$appUrl}/kalkulator-paparan), atau Lapor Asap ({$appUrl}/lapor-asap).
- Tangani Laporan Titik Asap: Jika pengguna ingin melapor pembakaran sampah/asap pekat/debu konstruksi, arahkan mengirim foto + caption "LAPOR [Lokasi]" atau format "LAPOR [Kategori] [Lokasi singkat]".

3. Batasan & Aturan Keamanan (Guardrails):
- Batasan Wilayah: Layanan HANYA mencakup 7 wilayah Eks-Karesidenan Kediri. Jika pengguna menanyakan kota di luar wilayah ini (misalnya Surabaya, Malang, Jakarta), jelaskan ramah bahwa NapasAman saat ini berfokus di Eks-Karesidenan Kediri dan berencana ekspansi ke seluruh Jawa Timur ke depannya.
- Batasan Medis: Kamu BUKAN dokter. Jika pengguna mengeluhkan sesak napas berat, asma kambuh parah, nyeri dada, atau gejala darurat, segera sarankan menghubungi fasilitas kesehatan/dokter terdekat.
- Di Luar Topik: Jika pengguna bertanya hal di luar kualitas udara, rute bersih, kesehatan pernapasan harian, atau NapasAman (matematika, politik, puisi, gosip), tolak halus dan arahkan kembali ke fitur NapasAman.
- Kejujuran Data: Jika data suatu titik tidak tersedia, katakan jujur data sedang diperbarui.
PROMPT;
    }

    /**
     * Bagian 2: Dynamic Real-Time Context Block from MySQL.
     */
    protected function buildDynamicContext(
        Collection $regions,
        string $userMessage,
        string $appUrl,
        ?array $locationInfo = null,
        ?Collection $nearbyReports = null
    ): string {
        $nowWib = now('Asia/Jakarta')->format('d M Y, H:i') . ' WIB';

        $regionLines = [];
        foreach ($regions as $region) {
            /** @var Region $region */
            $aq = $region->latestAirQuality;
            if (! $aq) {
                continue;
            }
            $trend = $this->airQualityService->getHourlyTrend($region);
            $jog = $this->airQualityService->getBestJoggingWindow($trend);
            $bestJog = $jog['primary']['hour'] ?? '05:00 - 06:30 WIB';
            $regionLines[] = sprintf(
                '- %s: AQI %d, PM2.5 %.1f µg/m³, PM10 %.1f µg/m³, NO2 %.1f µg/m³ (%s) | Jam jogging terbersih: %s',
                $region->name,
                (int) $aq->aqi,
                (float) $aq->pm25,
                (float) $aq->pm10,
                (float) $aq->no2,
                $aq->category,
                $bestJog
            );
        }

        // Active smoke reports (nearby if Share Location, else recent active across Kediri Raya)
        $reports = $nearbyReports;
        if ($reports === null) {
            $reports = SmokeReport::with('region')
                ->whereIn('status', ['dilaporkan', 'diverifikasi'])
                ->latest()
                ->limit(5)
                ->get();
        }

        if ($reports->isEmpty()) {
            $reportsText = 'Tidak ada laporan titik asap aktif saat ini.';
        } else {
            $reportsText = $reports->map(function (SmokeReport $rep) {
                $distStr = isset($rep->distance_km) ? " (~{$rep->distance_km} km dari pengguna)" : '';
                return sprintf(
                    '- [%s] %s%s: %s (Status: %s)',
                    $rep->region->name ?? 'Kediri Raya',
                    $rep->category,
                    $distStr,
                    mb_substr($rep->description, 0, 120),
                    $rep->status_label
                );
            })->implode("\n");
        }

        $gpsStr = 'Tidak ada';
        $nearestStr = 'Tidak terdeteksi dari GPS';
        if ($locationInfo && isset($locationInfo['lat'], $locationInfo['lng'])) {
            $gpsStr = sprintf('%.5f, %.5f', $locationInfo['lat'], $locationInfo['lng']);
            if (! ($locationInfo['within_kediri_raya'] ?? true)) {
                $nearestStr = 'DI LUAR WILAYAH EKS-KARESIDENAN KEDIRI (jarak ke '
                    . ($locationInfo['region']->name ?? 'Kediri') . " ~{$locationInfo['distance_km']} km)";
            } elseif (! empty($locationInfo['region'])) {
                $nearestStr = "{$locationInfo['region']->name} (jarak ~{$locationInfo['distance_km']} km dari pusat wilayah)";
            }
        }

        $regionsBlock = implode("\n", $regionLines);

        return <<<CTX
[KONTEKS REAL-TIME SISTEM]
Waktu saat ini: {$nowWib}
URL Web NapasAman: {$appUrl}

Data Kualitas Udara Terbaru (Eks-Karesidenan Kediri):
{$regionsBlock}

Laporan Titik Asap Aktif Terdekat:
{$reportsText}

Informasi Pesan Pengguna:
- Lokasi GPS (jika Share Location): {$gpsStr}
- Wilayah Terdekat Terdeteksi: {$nearestStr}
- Pesan Pengguna: "{$userMessage}"
CTX;
    }

    /**
     * Bagian 4: Few-Shot Examples to keep tone, format, and guardrails rock-solid.
     */
    protected function buildFewShotContents(string $appUrl): array
    {
        return [
            [
                'role' => 'user',
                'parts' => [['text' => 'Pesan Pengguna: "Halo, ini bot apa ya?"']],
            ],
            [
                'role' => 'model',
                'parts' => [[
                    'text' => "Halo Kak! 👋 Saya *Sobat Napas* dari *NapasAman*, asisten kualitas udara khusus *Wilayah Eks-Karesidenan Kediri* (Kediri, Tulungagung, Blitar, Nganjuk, dan Trenggalek).\n\n"
                        . "Kamu bisa langsung chat santai atau pakai cara cepat ini:\n"
                        . "• Kirim *Share Location* untuk cek PM2.5 di lokasimu sekarang\n"
                        . "• Tanya bebas, misal: _\"Aman gak jogging sore ini di Tulungagung?\"_\n"
                        . "• Kirim *foto + caption LAPOR* untuk lapor titik bakar sampah/asap pekat\n\n"
                        . "Mau cek kualitas udara di daerah mana hari ini, Kak? 🌿",
                ]],
            ],
            [
                'role' => 'user',
                'parts' => [['text' => 'Pesan Pengguna: "Aman gak lari sore ini di sekitar Kota Kediri?"']],
            ],
            [
                'role' => 'model',
                'parts' => [[
                    'text' => "Halo Kak! Berdasarkan data terbaru untuk *Kota Kediri*:\n"
                        . "• *US AQI:* 112 (Tidak Sehat bagi Kelompok Sensitif)\n"
                        . "• *PM2.5:* 40,2 µg/m³ _(1 jam aktivitas luar ≈ paparan ringan setara sebagian kecil batang rokok)_\n\n"
                        . "*Saran untuk Jogging:* 🏃 Sore ini kurang ideal untuk lari berat di pinggir jalan raya karena paparan asap kendaraan sedang tinggi. Sebaiknya pilih jalur rimbun/taman, kurangi durasi, atau geser ke pagi lebih awal besok.\n\n"
                        . "Cek perbandingan Rute Terbersih di sini ya: 🔗 {$appUrl}/rute-bersih",
                ]],
            ],
            [
                'role' => 'user',
                'parts' => [['text' => 'Lokasi GPS: -8.0657, 111.9025 | Wilayah Terdekat: Kabupaten Tulungagung | Pesan Pengguna: "[Share Location]"']],
            ],
            [
                'role' => 'model',
                'parts' => [[
                    'text' => "Lokasi terdeteksi di sekitar *Kabupaten Tulungagung* 📍\n"
                        . "• *US AQI:* 64 (Sedang)\n"
                        . "• *PM2.5:* 18,5 µg/m³\n"
                        . "• *Titik Asap Aktif:* Ada 1 laporan pembakaran sampah di sekitar jalur timur (hindari area tersebut sementara).\n\n"
                        . "*Rekomendasi Harian:*\n"
                        . "• *Komuter:* Aman beraktivitas, pakai masker jika melewati jalan padat.\n"
                        . "• *Keluarga (Anak/Lansia):* Masih cukup aman untuk aktivitas ringan di luar.\n\n"
                        . "Lihat peta rute rendah polusi: 🔗 {$appUrl}/rute-bersih",
                ]],
            ],
            [
                'role' => 'user',
                'parts' => [['text' => 'Pesan Pengguna: "Cek kualitas udara Jakarta dong"']],
            ],
            [
                'role' => 'model',
                'parts' => [[
                    'text' => "Maaf ya Kak 🙏 Saat ini *NapasAman* masih berfokus pada layanan pilot di 7 wilayah *Eks-Karesidenan Kediri* (Kota/Kab. Kediri, Tulungagung, Kota/Kab. Blitar, Nganjuk, dan Trenggalek), sebelum nantinya dikembangkan ke seluruh Jawa Timur.\n\n"
                        . "Kalau Kakak ingin cek udara atau rute bersih di salah satu dari 7 daerah tersebut, langsung ketik saja nama kotanya ya! 🌿",
                ]],
            ],
            [
                'role' => 'user',
                'parts' => [['text' => 'Pesan Pengguna: "Buatkan puisi cinta dong bot"']],
            ],
            [
                'role' => 'model',
                'parts' => [[
                    'text' => "Wah, maaf Kak, saya khusus bantu urusan _kualitas udara bersih, rute rendah polusi, dan pelaporan asap_ di wilayah Eks-Karesidenan Kediri saja 😅\n\n"
                        . "Mau saya cekkan kondisi udara atau jam jogging paling aman di daerahmu hari ini? 🌿",
                ]],
            ],
        ];
    }

    /**
     * Convert any stray Markdown bold (**text**) or headers (###) into clean WhatsApp formatting.
     */
    protected function sanitizeForWhatsApp(string $text): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $text);
        $text = preg_replace('/^#{1,6}\s+(.+)$/m', '*$1*', $text);

        return trim($text);
    }
}
