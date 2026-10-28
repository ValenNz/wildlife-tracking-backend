<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $logs = [
            [1, 1, 'LOGIN', '2026-10-19 08:00:00', 'User login ke sistem'],
            [2, 2, 'VIEW_GPS', '2026-10-19 09:30:00', 'Melihat data GPS SATWA-281'],
            [3, 3, 'CREATE_REPORT', '2026-10-19 10:30:00', 'Membuat laporan insiden'],
            [4, 1, 'VERIFY_PREDICTION', '2026-10-19 11:00:00', 'Memverifikasi prediksi AI'],
            [5, 2, 'GENERATE_REPORT', '2026-10-19 12:00:00', 'Generate laporan mingguan'],
        ];
        foreach ($logs as $l) {
            DB::table('activity_log')->updateOrInsert(
                ['log_id' => $l[0]],
                ['user_id' => $l[1], 'action' => $l[2], 'timestamp' => $l[3], 'details' => $l[4]]
            );
        }
    }
}
