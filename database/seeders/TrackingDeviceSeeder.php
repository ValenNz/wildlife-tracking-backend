<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrackingDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            [1, 1, 'Lotek Iridium TrackLite', '2026-01-21', 12, 1, 'normal', 87, 4, '2026-10-19 10:00:00'],
            [2, 2, 'Lotek GPS-Plus', '2026-08-22', 12, 1, 'normal', 53, 3, '2026-10-19 09:30:00'],
            [3, 3, 'Televilt Telemetry', '2026-02-12', 12, 1, 'normal', 98, 4, '2026-10-19 11:15:00'],
            [4, 4, 'Lotek GPS-Plus', '2026-04-07', 12, 1, 'damaged', 25, 1, '2026-10-18 15:20:00'],
            [5, 5, 'Lotek GPS-Plus', '2026-07-18', 12, 1, 'lost', 0, 0, '2026-10-15 08:45:00'],
        ];
        foreach ($devices as $d) {
            DB::table('tracking_devices')->updateOrInsert(
                ['device_id' => $d[0]],
                ['animal_id' => $d[1], 'model' => $d[2], 'installation_date' => $d[3], 'battery_life_months' => $d[4], 'is_active' => $d[5], 'device_condition' => $d[6], 'battery_level' => $d[7], 'signal_strength' => $d[8], 'last_recorded_at' => $d[9]]
            );
        }
    }
}
