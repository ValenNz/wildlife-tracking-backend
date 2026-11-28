<?php

namespace Tests\Feature;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Models\GuideShift;
use App\Models\Notification;
use App\Models\TourRoute;
use App\Models\User;
use Tests\TestCase;

class TrailSafetyAndPorterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    public function test_quote_and_booking_include_porter_addon_pricing(): void
    {
        $headers = $this->loginAs('pengunjung');
        $route = TourRoute::where('slug', 'bukit-lawang-orangutan-trek')->firstOrFail();
        $shift = GuideShift::where('route_id', $route->route_id)
            ->where('status', 'scheduled')
            ->whereDate('shift_date', '>', now()->toDateString())
            ->firstOrFail();

        $quoteResponse = $this->postJson('/api/tourism/quote', [
            'route_id' => $route->route_id,
            'visit_date' => $shift->shift_date->toDateString(),
            'wni_count' => 1,
            'porter_count' => 2,
        ]);

        $quoteResponse->assertOk();
        $lines = collect($quoteResponse->json('data.lines'));
        $porterLine = $lines->firstWhere('code', 'addon_porter');
        $this->assertNotNull($porterLine);
        $this->assertSame(2, $porterLine['quantity']);
        $this->assertSame((int) $route->porter_fee_per_day, $porterLine['unit_price']);
        $this->assertSame(2 * (int) $route->porter_fee_per_day * (int) $route->duration_days, $porterLine['subtotal']);

        $bookingResponse = $this->postJson('/api/visitor/bookings', [
            'route_id' => $route->route_id,
            'shift_id' => $shift->shift_id,
            'wni_count' => 1,
            'porter_count' => 2,
            'contact_name' => 'Budi Santoso',
            'contact_phone' => '081234567890',
            'emergency_contact' => 'Siti 081298765432',
            'payment_method' => 'qris',
            'participants' => [
                ['full_name' => 'Budi Santoso', 'visitor_type' => 'wni', 'age' => 28],
            ],
        ], $headers);

        $bookingResponse->assertCreated()
            ->assertJsonPath('data.porter_count', 2)
            ->assertJsonPath('data.total_amount', $quoteResponse->json('data.total'));
    }

    public function test_route_safety_endpoint_returns_contacts_posts_and_ai_emergencies(): void
    {
        $user = User::firstOrFail();
        $upload = AiMediaUpload::create([
            'uploaded_by' => $user->user_id,
            'media_type' => 'audio',
            'file_path' => 'ai/sample-sos.wav',
            'file_name' => 'sample-sos.wav',
            'file_size' => 1024,
            'mime_type' => 'audio/wav',
            'recorded_at' => now(),
            'uploaded_at' => now(),
        ]);

        AiPrediction::create([
            'media_id' => $upload->media_id,
            'analysis_type' => 'audio',
            'confidence' => 0.92,
            'sound_category' => 'chainsaw',
            'sound_label' => 'Suara Gergaji Mesin',
            'threat_level' => 'critical',
            'ai_summary' => 'Terdeteksi suara gergaji mesin di koridor atas.',
            'model_version' => 'yamnet-test-v1',
            'processing_status' => 'completed',
            'processed_at' => now(),
        ]);

        $response = $this->getJson('/api/tourism/routes/bukit-lawang-orangutan-trek/safety');

        $response->assertOk()
            ->assertJsonPath('data.trail_status', 'darurat')
            ->assertJsonCount(4, 'data.emergency_contacts');

        $this->assertNotEmpty($response->json('data.post_details'));
        $this->assertSame('Suara Gergaji Mesin', $response->json('data.ai_emergencies.0.label'));
    }

    public function test_emergency_alert_endpoint_notifies_rangers_and_admins_instantly(): void
    {
        $headers = $this->loginAs('pengunjung');

        $response = $this->postJson('/api/tourism/routes/bukit-lawang-orangutan-trek/emergency-alert', [
            'pos_code' => 'POS-1',
            'emergency_type' => 'cedera',
            'description' => 'Peserta terkilir di tanjakan Pos 1 dan butuh evakuasi.',
            'latitude' => 3.5551,
            'longitude' => 98.1140,
        ], $headers);

        $response->assertCreated()
            ->assertJsonPath('data.pos_code', 'POS-1')
            ->assertJsonPath('data.trail_status', 'darurat');

        $alertCode = $response->json('data.alert_code');
        $this->assertStringStartsWith('SOS-', $alertCode);
        $this->assertGreaterThan(0, $response->json('data.notified_rangers'));
        $this->assertGreaterThan(
            0,
            Notification::where('notification_type', 'risk_zone')
                ->where('message', 'like', "%{$alertCode}%")
                ->count()
        );
    }
}
