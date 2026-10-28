<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $notifs = [
            [1, 1, 'risk_zone', 'SATWA-281 memasuki zona perkebunan', 2, 2, 2, 0, '2026-10-19 09:45:00'],
            [2, 2, 'device_alert', 'Baterai perangkat SATWA-387 lemah', 3, 3, 3, 1, '2026-10-19 11:20:00'],
            [3, 3, 'system', 'Laporan harian siap diunduh', null, null, null, 1, '2026-10-19 08:00:00'],
            [4, 1, 'risk_zone', 'SATWA-968 terdeteksi di zona lindung', 5, 5, 5, 0, '2026-10-15 09:00:00'],
            [5, 2, 'device_alert', 'Perangkat SATWA-387 kehilangan sinyal', 3, 3, 3, 0, '2026-10-19 11:25:00'],
        ];
        foreach ($notifs as $n) {
            DB::table('notifications')->updateOrInsert(
                ['notif_id' => $n[0]],
                ['user_id' => $n[1], 'notification_type' => $n[2], 'message' => $n[3], 'animal_id' => $n[4], 'zone_id' => $n[5], 'device_id' => $n[6], 'is_read' => $n[7], 'created_at' => $n[8]]
            );
        }
    }
}
