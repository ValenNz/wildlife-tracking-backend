<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [1, 'Zona Konservasi Utara', 'koridor_ekologis', 3.671430, 97.878119, 2.313, null, 1, '2026-10-19 10:00:00'],
            [2, 'Zona Perkebunan Tengah', 'perkebunan', 3.747409, 97.969478, 1.271, null, 2, '2026-10-19 10:00:00'],
            [3, 'Zona Permukiman Selatan', 'permukiman', 3.674023, 97.433463, 1.363, null, 1, '2026-10-19 10:00:00'],
            [4, 'Zona Jalan Raya Timur', 'jalan_raya', 3.918911, 97.535144, 1.514, null, 3, '2026-10-19 10:00:00'],
            [5, 'Zona Hutan Lindung Barat', 'koridor_ekologis', 3.621963, 97.818243, 2.471, null, 2, '2026-10-19 10:00:00'],
        ];
        foreach ($zones as $z) {
            DB::table('geo_zones')->updateOrInsert(
                ['zone_id' => $z[0]],
                ['zone_name' => $z[1], 'zone_type' => $z[2], 'center_lat' => $z[3], 'center_lon' => $z[4], 'radius_km' => $z[5], 'polygon_geojson' => $z[6], 'created_by' => $z[7], 'created_at' => $z[8]]
            );
        }
    }
}
