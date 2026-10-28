<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GpsReadingSeeder extends Seeder
{
    public function run(): void
    {
        $readings = [
            [1, 1, 3.658620, 97.731964, '2026-10-19 10:00:00', 489.16, 17.06, 'f468e4c385bf213285a191088c66bbe68652d3caa6f18f49dd80f77e36f6ba57'],
            [2, 2, 3.967782, 97.381211, '2026-10-19 09:30:00', 379.69, 8.65, '4a33e9b80e758d0ee44c4cbbf53ea104f2485dda61ba0747b173c573f537b0f9'],
            [3, 3, 3.730805, 97.613118, '2026-10-19 11:15:00', 517.81, 16.03, 'fdad1e5a093cfaa2f8d4f1db9be8ed9646c7e426f8d303753dc0ff995d027b9a'],
            [4, 4, 3.756809, 97.450071, '2026-10-18 15:20:00', 205.69, 17.35, '3f25373bcb50467e3b29408a5d03eb790ab900e892a06bea001b69cef7936db7'],
            [5, 5, 3.899724, 97.045250, '2026-10-15 08:45:00', 125.96, 13.73, '633b3eb374f92b1e3b49f2bbdd30a30601bc8bce1353f2e7f46d46af8c9232f8'],
        ];
        foreach ($readings as $r) {
            DB::table('gps_readings')->updateOrInsert(
                ['reading_id' => $r[0]],
                ['device_id' => $r[1], 'latitude' => $r[2], 'longitude' => $r[3], 'recorded_at' => $r[4], 'altitude_meters' => $r[5], 'accuracy_meters' => $r[6], 'checksum' => $r[7]]
            );
        }
    }
}
