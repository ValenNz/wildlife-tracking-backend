<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Tests\TestCase;

class NotificationRoleVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    public function test_all_six_roles_can_access_notifications_and_unread_count(): void
    {
        foreach (['admin', 'peneliti', 'lapangan', 'kebijakan', 'loket', 'pengunjung'] as $roleKey) {
            $headers = $this->loginAs($roleKey);
            $this->getJson('/api/notifications', $headers)->assertOk();
            $this->getJson('/api/notifications/unread-count', $headers)
                ->assertOk()
                ->assertJsonStructure(['data' => ['unread', 'allowed_types']]);
        }
    }

    public function test_non_conservation_roles_never_see_risk_zone_or_device_alert_notifications(): void
    {
        $visitor = User::where('email', 'pengunjung@wildlife.id')->firstOrFail();
        $cashier = User::where('email', 'loket@wildlife.id')->firstOrFail();

        $hiddenThreat = Notification::create([
            'user_id' => $visitor->user_id,
            'notification_type' => 'risk_zone',
            'message' => 'Ancaman perburuan terdeteksi kamera jebak #12',
            'is_read' => false,
            'created_at' => now(),
        ]);
        $hiddenDevice = Notification::create([
            'user_id' => $cashier->user_id,
            'notification_type' => 'device_alert',
            'message' => 'Baterai kalung GPS #4 kritis (5%)',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $visitorHeaders = $this->loginAs('pengunjung');
        $visitorList = $this->getJson('/api/notifications', $visitorHeaders)->assertOk()->json('data');
        $this->assertFalse(collect($visitorList)->pluck('notif_id')->contains($hiddenThreat->notif_id));
        $this->getJson('/api/notifications?type=risk_zone', $visitorHeaders)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/notifications/{$hiddenThreat->notif_id}", $visitorHeaders)->assertNotFound();

        $cashierHeaders = $this->loginAs('loket');
        $cashierList = $this->getJson('/api/notifications', $cashierHeaders)->assertOk()->json('data');
        $this->assertFalse(collect($cashierList)->pluck('notif_id')->contains($hiddenDevice->notif_id));
        $this->getJson('/api/notifications?type=device_alert', $cashierHeaders)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/notifications/{$hiddenDevice->notif_id}", $cashierHeaders)->assertNotFound();
    }

    public function test_notification_service_blocks_disallowed_types_for_visitor_and_cashier(): void
    {
        $service = app(NotificationService::class);
        $admin = User::where('email', 'admin@wildlife.id')->firstOrFail();
        $visitor = User::where('email', 'pengunjung@wildlife.id')->firstOrFail();
        $cashier = User::where('email', 'loket@wildlife.id')->firstOrFail();

        $this->assertTrue($service->notifyUser($admin->user_id, 'risk_zone', 'Ancaman terdeteksi untuk admin.', [], 0));
        $this->assertFalse($service->notifyUser($visitor->user_id, 'risk_zone', 'Ancaman tidak boleh ke pengunjung.', [], 0));
        $this->assertFalse($service->notifyUser($cashier->user_id, 'device_alert', 'Alert perangkat tidak boleh ke loket.', [], 0));
        $this->assertTrue($service->notifyUser($visitor->user_id, 'system', 'Tiket Anda berhasil diterbitkan.', [], 0));
    }
}
