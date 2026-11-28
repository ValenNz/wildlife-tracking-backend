<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ExportController;
use App\Models\AiPrediction;
use App\Models\Notification;
use App\Models\Role;
use App\Models\TrackingDevice;
use App\Models\User;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    private function userId(string $username): int
    {
        return (int) User::where('username', $username)->value('user_id');
    }

    public function test_deactivating_user_via_put_revokes_access(): void
    {
        $peneliti = $this->loginAs('peneliti');
        $admin = $this->loginAs('admin');

        $this->putJson('/api/users/' . $this->userId('peneliti'), ['status' => 'nonaktif'], $admin)->assertOk();
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/me', $peneliti)->assertStatus(401);
    }

    public function test_inactive_user_token_is_rejected_even_if_not_revoked(): void
    {
        $peneliti = $this->loginAs('peneliti');
        User::where('username', 'peneliti')->update(['status' => 'nonaktif']);

        $this->getJson('/api/me', $peneliti)->assertStatus(401);
    }

    public function test_admin_cannot_deactivate_self_via_put(): void
    {
        $admin = $this->loginAs('admin');

        $this->putJson('/api/users/' . $this->userId('admin'), ['status' => 'nonaktif'], $admin)->assertStatus(422);
    }

    public function test_admin_password_reset_revokes_target_sessions(): void
    {
        $peneliti = $this->loginAs('peneliti');
        $admin = $this->loginAs('admin');

        $this->putJson('/api/users/' . $this->userId('peneliti'), ['password' => 'passwordBaru123'], $admin)->assertOk();
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/me', $peneliti)->assertStatus(401);
    }

    public function test_reanalysis_cannot_overwrite_verified_prediction(): void
    {
        $prediction = AiPrediction::firstOrFail();
        $prediction->update(['verification_status' => 'confirmed', 'verified_at' => now()]);
        $headers = $this->loginAs('kebijakan');

        $this->postJson("/api/media-uploads/{$prediction->media_id}/predictions", [], $headers)->assertStatus(409);
        $this->assertSame('confirmed', $prediction->fresh()->verification_status);
    }

    public function test_admin_cannot_lock_self_out_of_role_management(): void
    {
        $admin = $this->loginAs('admin');
        $roleId = Role::where('name_role', 'administrator_sistem')->value('role_id');
        $manageRoles = \App\Models\Permission::where('name_permission', 'manage_roles')->value('permission_id');

        $this->putJson("/api/roles/{$roleId}/permissions", ['permission_ids' => []], $admin)->assertStatus(422);
        $this->deleteJson("/api/roles/{$roleId}/permissions/{$manageRoles}", [], $admin)->assertStatus(422);
        $this->getJson('/api/roles', $admin)->assertOk();
    }

    public function test_system_roles_cannot_be_renamed_or_deleted(): void
    {
        $admin = $this->loginAs('admin');
        $roleId = Role::where('name_role', 'peneliti_ekologi')->value('role_id');

        $this->putJson("/api/roles/{$roleId}", ['name_role' => 'peneliti_baru'], $admin)->assertStatus(422);
        $this->putJson("/api/roles/{$roleId}", ['description' => 'Deskripsi baru'], $admin)->assertOk();
        $this->deleteJson("/api/roles/{$roleId}", [], $admin)->assertStatus(409);
    }

    public function test_sync_status_uses_permission_not_role_name(): void
    {
        $this->patchJson('/api/incidents/' . \App\Models\IncidentReport::value('report_id') . '/sync-status',
            ['sync_status' => 'synced'], $this->loginAs('admin'))->assertOk();
        $this->app['auth']->forgetGuards();
        $this->patchJson('/api/incidents/' . \App\Models\IncidentReport::value('report_id') . '/sync-status',
            ['sync_status' => 'synced'], $this->loginAs('peneliti'))->assertStatus(403);
    }

    public function test_update_requires_update_data_permission(): void
    {
        $roleId = Role::where('name_role', 'konservasionis_lapangan')->value('role_id');
        $updateData = \App\Models\Permission::where('name_permission', 'update_data')->value('permission_id');
        Role::find($roleId)->permissions()->detach($updateData);

        $headers = $this->loginAs('lapangan');
        $incident = \App\Models\IncidentReport::where('user_id', $this->userId('lapangan'))->firstOrFail();

        $this->putJson("/api/incidents/{$incident->report_id}", ['description' => 'Ubah'], $headers)->assertStatus(403);
    }

    public function test_gps_reading_rejected_for_camera_device(): void
    {
        $camera = TrackingDevice::where('device_type', 'camera')->firstOrFail();

        $this->postJson('/api/gps-readings', [
            'device_id' => $camera->device_id, 'latitude' => 3.6, 'longitude' => 98.1,
            'recorded_at' => now()->toDateTimeString(),
        ], $this->loginAs('lapangan'))->assertStatus(422)->assertJsonValidationErrors('device_id');
    }

    public function test_low_battery_from_gps_payload_raises_device_alert(): void
    {
        $device = TrackingDevice::where('device_type', 'gps')->where('device_condition', 'normal')->firstOrFail();
        $device->update(['battery_level' => 90]);
        Notification::query()->delete();

        $this->postJson('/api/gps-readings', [
            'device_id' => $device->device_id, 'latitude' => 3.6, 'longitude' => 98.1,
            'recorded_at' => now()->toDateTimeString(), 'battery_level' => 5,
        ], $this->loginAs('lapangan'))->assertCreated();

        $this->assertTrue(Notification::where('notification_type', 'device_alert')->where('device_id', $device->device_id)->exists());
    }

    public function test_negative_limit_does_not_disable_cap(): void
    {
        $data = $this->getJson('/api/gps-readings?limit=-1', $this->loginAs('admin'))->assertOk()->json('data');

        $this->assertCount(1, $data);
    }

    public function test_csv_cells_are_neutralised_against_formula_injection(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://x\")", ExportController::csvCell('=HYPERLINK("http://x")'));
        $this->assertSame("'@SUM(A1)", ExportController::csvCell('@SUM(A1)'));
        $this->assertSame('-3.5', ExportController::csvCell('-3.5'));
        $this->assertSame(-3.5, ExportController::csvCell(-3.5));
        $this->assertSame('Satwa sehat', ExportController::csvCell('Satwa sehat'));

        $headers = $this->loginAs('admin');
        $this->postJson('/api/incidents', [
            'incident_type' => 'observasi', 'description' => '=HYPERLINK("http://x","klik")',
            'timestamp' => now()->toDateTimeString(), 'latitude' => 3.6, 'longitude' => 98.1,
        ], $headers)->assertCreated();

        $csv = $this->get('/api/export/incidents/csv', $headers)->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_media_file_is_served_with_nosniff(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $media = \App\Models\AiMediaUpload::firstOrFail();
        \Illuminate\Support\Facades\Storage::disk('local')->put($media->file_path, 'x');

        $this->get("/api/media-uploads/{$media->media_id}/file", $this->loginAs('admin'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
