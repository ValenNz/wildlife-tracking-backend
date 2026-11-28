<?php

namespace Tests\Feature;

use App\Models\TrackingDevice;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
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

    private function deviceToken(TrackingDevice $device): array
    {
        $token = $this->postJson("/api/devices/{$device->device_id}/api-token", [], $this->loginAs('peneliti'))
            ->assertCreated()->json('data.token');

        return ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
    }

    public function test_device_token_sends_only_its_own_data_and_survives_idle(): void
    {
        $gps = TrackingDevice::where('device_type', 'gps')->where('is_active', true)->firstOrFail();
        $other = TrackingDevice::where('device_type', 'gps')->where('device_id', '!=', $gps->device_id)->firstOrFail();
        $h = $this->deviceToken($gps);

        $point = fn (int $id) => ['device_id' => $id, 'latitude' => 3.55, 'longitude' => 98.11, 'recorded_at' => now()->subMinute()->toDateTimeString()];
        $this->postJson('/api/gps-readings', $point($gps->device_id), $h)->assertCreated();
        $this->patchJson("/api/devices/{$gps->device_id}/telemetry", ['battery_level' => 80], $h)->assertOk();

        $this->postJson('/api/gps-readings', $point($other->device_id), $h)->assertForbidden();
        $this->patchJson("/api/devices/{$other->device_id}/telemetry", ['battery_level' => 80], $h)->assertForbidden();
        foreach (['/api/animals', '/api/gps-readings', '/api/me', '/api/users'] as $url) {
            $this->getJson($url, $h)->assertForbidden();
        }

        $this->travel(3)->days();
        $this->patchJson("/api/devices/{$gps->device_id}/telemetry", ['battery_level' => 70], $h)->assertOk();
    }

    public function test_new_token_revokes_old_one_and_lost_devices_are_rejected(): void
    {
        $gps = TrackingDevice::where('device_type', 'gps')->where('is_active', true)->firstOrFail();
        $old = $this->deviceToken($gps);
        $new = $this->deviceToken($gps);

        $this->patchJson("/api/devices/{$gps->device_id}/telemetry", ['battery_level' => 80], $old)->assertUnauthorized();
        $this->patchJson("/api/devices/{$gps->device_id}/telemetry", ['battery_level' => 80], $new)->assertOk();

        $gps->update(['device_condition' => 'lost']);
        $this->patchJson("/api/devices/{$gps->device_id}/telemetry", ['battery_level' => 80], $new)->assertStatus(409);
        $this->postJson('/api/gps-readings', ['device_id' => $gps->device_id, 'latitude' => 3.5, 'longitude' => 98.1, 'recorded_at' => now()->toDateTimeString()], $new)
            ->assertUnprocessable();

        $this->deleteJson("/api/devices/{$gps->device_id}/api-token", [], $this->loginAs('peneliti'))->assertOk()->assertJsonPath('data.revoked', 1);
        $this->patchJson("/api/devices/{$gps->device_id}/telemetry", ['battery_level' => 80], $new)->assertUnauthorized();
    }

    public function test_only_master_data_roles_manage_device_tokens(): void
    {
        $id = TrackingDevice::value('device_id');
        foreach (['lapangan', 'kebijakan', 'pengunjung'] as $user) {
            $this->postJson("/api/devices/{$id}/api-token", [], $this->loginAs($user))->assertForbidden();
        }
    }
}
