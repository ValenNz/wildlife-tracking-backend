<?php

namespace Tests\Feature;

use App\Models\GpsReading;
use App\Models\TrackingDevice;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class GpsSimulatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    private function collar(): TrackingDevice
    {
        $collar = TrackingDevice::where('serial_number', 'GPS-0002')->firstOrFail();
        $collar->update(['is_active' => true, 'device_condition' => 'normal']);
        $collar->animal->update(['is_active' => true]);

        return $collar;
    }

    public function test_disabled_simulator_writes_nothing(): void
    {
        config(['services.gps_simulator.enabled' => false]);
        $before = GpsReading::count();

        $this->artisan('tracking:simulate-gps')->assertSuccessful();

        $this->assertSame($before, GpsReading::count());
    }

    public function test_each_run_appends_a_nearby_point_for_active_collars(): void
    {
        config(['services.gps_simulator.enabled' => true]);
        $collar = $this->collar();
        GpsReading::create([
            'device_id' => $collar->device_id, 'latitude' => 3.6415, 'longitude' => 98.0395,
            'recorded_at' => now()->subSeconds(10),
        ]);
        $before = GpsReading::where('device_id', $collar->device_id)->count();

        $this->artisan('tracking:simulate-gps')->assertSuccessful();

        $readings = GpsReading::where('device_id', $collar->device_id)->orderByDesc('recorded_at')->get();
        $this->assertCount($before + 1, $readings);

        $latest = $readings->first();
        $this->assertTrue($latest->recorded_at->gt(now()->subSeconds(5)));
        $this->assertEqualsWithDelta(3.6415, $latest->latitude, 0.0002);
        $this->assertEqualsWithDelta(98.0395, $latest->longitude, 0.0002);
        $this->assertNotNull($collar->fresh()->last_recorded_at);
    }

    public function test_inactive_collar_is_skipped(): void
    {
        config(['services.gps_simulator.enabled' => true]);
        $collar = $this->collar();
        GpsReading::create([
            'device_id' => $collar->device_id, 'latitude' => 3.6415, 'longitude' => 98.0395,
            'recorded_at' => now()->subSeconds(10),
        ]);
        $collar->update(['is_active' => false]);
        $before = GpsReading::where('device_id', $collar->device_id)->count();

        $this->artisan('tracking:simulate-gps')->assertSuccessful();

        $this->assertSame($before, GpsReading::where('device_id', $collar->device_id)->count());
    }

    public function test_simulator_is_scheduled_every_ten_seconds(): void
    {
        $event = collect(Schedule::events())->first(fn ($e) => str_contains($e->command ?? '', 'tracking:simulate-gps'));

        $this->assertNotNull($event);
        $this->assertSame(10, $event->repeatSeconds);
    }
}
