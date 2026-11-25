<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\SmokeReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NapasAmanTest extends TestCase
{
    use DatabaseTransactions;

    public function test_home_dashboard_loads_all_seven_kediri_raya_regions(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('NapasAman');
        $response->assertSee('Cek Kualitas Udara.');
        $response->assertSee('napasaman-logo.png');
        $response->assertSee('manifest.json');
        $response->assertSee('Kota Kediri');
        $response->assertSee('Kabupaten Kediri');
        $response->assertSee('Kabupaten Tulungagung');
        $response->assertSee('Kota Blitar');
        $response->assertSee('Kabupaten Blitar');
        $response->assertSee('Kabupaten Nganjuk');
        $response->assertSee('Kabupaten Trenggalek');
    }

    public function test_clean_route_page_loads_successfully(): void
    {
        $response = $this->get('/rute-bersih');

        $response->assertOk();
        $response->assertSee('Peta Navigasi Rute Rendah Polusi');
        $response->assertSee('Simpang Lima Gumul');
        $response->assertSee('Alun-Alun Tulungagung');
        $response->assertSee('Alun-Alun Kota Blitar');
    }

    public function test_exposure_calculator_page_and_api_work(): void
    {
        $this->get('/kalkulator-paparan')
            ->assertOk()
            ->assertSee('Kalkulator');

        $response = $this->postJson('/kalkulator-paparan/hitung', [
            'region_slug' => 'kota-kediri',
            'activity' => 'motor',
            'duration_minutes' => 60,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'region',
                'aqi',
                'category',
                'result' => [
                    'total_inhaled_ug',
                    'cigarettes',
                    'tips',
                ],
            ]);
    }

    public function test_smoke_report_page_and_submission_validate_kediri_raya_bounds(): void
    {
        $this->get('/lapor-asap')->assertOk()->assertSee('Lapor Titik Asap');

        $kediri = Region::where('slug', 'kota-kediri')->firstOrFail();

        // Reject outside Eks-Karesidenan Kediri (e.g. Jakarta)
        $this->post('/lapor-asap', [
            'region_id' => $kediri->id,
            'reporter_name' => 'Warga Uji',
            'category' => 'Pembakaran Sampah Terbuka',
            'description' => 'Laporan uji di luar batas wilayah Kediri Raya.',
            'latitude' => -6.2000,
            'longitude' => 106.8166,
        ])->assertSessionHasErrors(['latitude', 'longitude']);

        // Accept valid Kediri Raya coordinates
        $this->post('/lapor-asap', [
            'region_id' => $kediri->id,
            'reporter_name' => 'Siti Rahayu',
            'reporter_phone' => '081234567899',
            'category' => 'Pembakaran Sampah Terbuka',
            'description' => 'Pembakaran sampah di lahan kosong Jl. Dhoho, asap pekat.',
            'latitude' => -7.8190,
            'longitude' => 112.0125,
        ])->assertRedirect(route('lapor-asap.index'));

        $this->assertDatabaseHas('smoke_reports', [
            'reporter_name' => 'Siti Rahayu',
            'category' => 'Pembakaran Sampah Terbuka',
            'status' => 'dilaporkan',
        ]);
    }

    public function test_whatsapp_bot_page_and_webhook_commands(): void
    {
        $this->get('/bot-wa')->assertOk()->assertSee('Sobat Napas');

        // 1. CEK KEDIRI (Fonnte format)
        $cek = $this->postJson('/api/whatsapp/webhook', [
            'sender' => '6281234500001',
            'message' => 'CEK KEDIRI',
        ]);
        $cek->assertOk()
            ->assertJsonPath('command', 'CEK');
        $this->assertStringContainsString('Kota Kediri', $cek->json('reply'));
        $this->assertStringContainsString('/rute-bersih', $cek->json('reply'));

        // 2. JOGGING TULUNGAGUNG (Meta Cloud API format)
        $jog = $this->postJson('/api/whatsapp/webhook', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '6281234500002',
                            'text' => ['body' => 'JOGGING TULUNGAGUNG'],
                        ]],
                    ],
                ]],
            ]],
        ]);
        $jog->assertOk()
            ->assertJsonPath('command', 'JOGGING');
        $this->assertStringContainsString('Kabupaten Tulungagung', $jog->json('reply'));

        // 3. LAPOR command saves to smoke_reports
        $beforeCount = SmokeReport::count();
        $lapor = $this->postJson('/api/whatsapp/webhook', [
            'sender' => '6281234500003',
            'message' => 'LAPOR SAMPAH Pembakaran sampah dekat Simpang Lima Gumul Kediri',
        ]);
        $lapor->assertOk()
            ->assertJsonPath('command', 'LAPOR');
        $this->assertEquals($beforeCount + 1, SmokeReport::count());
        $this->assertDatabaseHas('whatsapp_logs', [
            'sender_phone' => '6281234500003',
            'command' => 'LAPOR',
        ]);
    }

    public function test_admin_panel_requires_auth_and_allows_status_update(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('napasaman-logo.png');

        $admin = User::where('email', 'admin@napasaman.id')->firstOrFail();
        $report = SmokeReport::firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard KPI Dampak');

        $this->actingAs($admin)
            ->patch(route('admin.reports.update-status', $report), [
                'status' => 'ditindaklanjuti',
                'admin_notes' => 'Sudah diedukasi oleh pengurus RT & Bank Sampah.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('smoke_reports', [
            'id' => $report->id,
            'status' => 'ditindaklanjuti',
            'admin_notes' => 'Sudah diedukasi oleh pengurus RT & Bank Sampah.',
        ]);
    }

    public function test_webhook_baileys_share_location_photo_report_and_gemini_fallback(): void
    {
        // 1. Share Location payload from Baileys (Kabupaten Tulungagung) with fallback (no live external Gemini call in test)
        config(['services.gemini.api_key' => '']);

        $locResponse = $this->postJson('/api/whatsapp/webhook', [
            'sender' => '6281234990001',
            'push_name' => 'Warga Tulungagung',
            'message_type' => 'location',
            'latitude' => -8.0657,
            'longitude' => 111.9025,
            'message' => '[Share Location: -8.0657, 111.9025]',
        ]);

        $locResponse->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('command', 'LOCATION');
        $this->assertStringContainsString('Kabupaten Tulungagung', $locResponse->json('reply'));
        $this->assertStringContainsString('/rute-bersih', $locResponse->json('reply'));

        // 2. Photo + "lapor" payload saves image to storage/app/public/reports/ and creates smoke_reports row
        $tinyPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $photoResponse = $this->postJson('/api/whatsapp/webhook', [
            'sender' => '6281234990002',
            'push_name' => 'Budi Nganjuk',
            'message_type' => 'image',
            'message' => 'LAPOR pembakaran sampah di pinggir jalan dekat pasar Nganjuk',
            'image_base64' => $tinyPngBase64,
            'image_mime' => 'image/png',
        ]);

        $photoResponse->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('command', 'LAPOR');

        $photoPath = $photoResponse->json('meta.photo_path');
        $this->assertNotNull($photoPath);
        $this->assertStringStartsWith('reports/', $photoPath);
        $this->assertTrue(Storage::disk('public')->exists($photoPath));

        // Clean up test uploaded image
        Storage::disk('public')->delete($photoPath);

        // 3. Free-form Q&A with mocked Gemini API response
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            '*generateContent*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => "Halo Kak! Berdasarkan data terbaru *Kota Kediri*, saat ini kondisi udara *Sedang*.\nCek rute bersih: http://127.0.0.1:8000/rute-bersih",
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $aiResponse = $this->postJson('/api/whatsapp/webhook', [
            'sender' => '6281234990003',
            'message' => 'Aman gak jogging sore ini di Kediri?',
        ]);

        $aiResponse->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('command', 'AI_CHAT')
            ->assertJsonPath('ai_powered', true);
        $this->assertStringContainsString('Kota Kediri', $aiResponse->json('reply'));
    }

    public function test_webhook_falls_back_cleanly_when_gemini_rate_limited(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            '*generateContent*' => Http::response(['error' => 'quota exceeded'], 429),
        ]);

        $fallbackResponse = $this->postJson('/api/whatsapp/webhook', [
            'sender' => '6281234990004',
            'message' => 'Kualitas udara Tulungagung gimana?',
        ]);

        $fallbackResponse->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('ai_powered', false);
        $this->assertStringContainsString('Kabupaten Tulungagung', $fallbackResponse->json('reply'));
    }
}
