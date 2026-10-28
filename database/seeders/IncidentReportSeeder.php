<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IncidentReportSeeder extends Seeder
{
    public function run(): void
    {
        $reports = [
            [1, 1, 'observasi', 'SATWA-742', 'Zona Konservasi Utara', 'Satwa terlihat sehat, bergerak normal', '2026-10-19 10:30:00', 'uploads/img_001.jpg', 'synced'],
            [2, 2, 'konflik', 'SATWA-281', 'Zona Perkebunan Tengah', 'Satwa masuk area perkebunan warga', '2026-10-19 09:45:00', 'uploads/img_002.jpg', 'synced'],
            [3, 3, 'device_alert', 'SATWA-803', 'Zona Permukiman Selatan', 'Perangkat GPS sinyal lemah', '2026-10-19 11:20:00', null, 'pending_sync'],
            [4, 1, 'observasi', 'SATWA-387', 'Zona Jalan Raya Timur', 'Satwa melintasi jalan raya', '2026-10-18 15:30:00', 'uploads/img_003.jpg', 'synced'],
            [5, 2, 'perambahan', 'SATWA-968', 'Zona Hutan Lindung Barat', 'Ditemukan aktivitas ilegal di zona lindung', '2026-10-15 09:00:00', 'uploads/img_004.jpg', 'failed'],
        ];
        foreach ($reports as $r) {
            DB::table('incident_reports')->updateOrInsert(
                ['report_id' => $r[0]],
                ['user_id' => $r[1], 'incident_type' => $r[2], 'animal_code' => $r[3], 'location' => $r[4], 'description' => $r[5], 'timestamp' => $r[6], 'photo_url' => $r[7], 'sync_status' => $r[8]]
            );
        }
    }
}
