<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalGeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $visits = [
            [1, 1, 1, 1, '2026-10-19 08:00:00', '2026-10-19 12:00:00'],
            [2, 2, 2, 2, '2026-10-19 09:00:00', '2026-10-19 14:00:00'],
            [3, 3, 3, 3, '2026-10-19 10:00:00', null],
            [4, 4, 4, 4, '2026-10-18 14:00:00', '2026-10-18 18:00:00'],
            [5, 5, 5, 5, '2026-10-15 07:00:00', '2026-10-15 11:00:00'],
        ];
        foreach ($visits as $v) {
            DB::table('animal_geo_zones')->updateOrInsert(
                ['visit_id' => $v[0]],
                ['animal_id' => $v[1], 'zone_id' => $v[2], 'entry_reading_id' => $v[3], 'entered_at' => $v[4], 'exited_at' => $v[5]]
            );
        }
    }
}
