<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\User;
use Illuminate\Database\Seeder;

class GeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'zone_id' => 1,
                'zone_name' => 'Zona Konservasi Utara',
                'zone_type' => 'koridor_ekologis',
                'center_lat' => 3.671430,
                'center_lon' => 97.878119,
                'radius_km' => 2.313,
                'polygon_geojson' => null,
                'created_by' => 1,
                'created_at' => '2026-10-19 10:00:00',
            ],
            [
                'zone_id' => 2,
                'zone_name' => 'Zona Perkebunan Tengah',
                'zone_type' => 'perkebunan',
                'center_lat' => 3.747409,
                'center_lon' => 97.969478,
                'radius_km' => 1.271,
                'polygon_geojson' => null,
                'created_by' => 2,
                'created_at' => '2026-10-19 10:00:00',
            ],
            [
                'zone_id' => 3,
                'zone_name' => 'Zona Permukiman Selatan',
                'zone_type' => 'permukiman',
                'center_lat' => 3.674023,
                'center_lon' => 97.433463,
                'radius_km' => 1.363,
                'polygon_geojson' => null,
                'created_by' => 1,
                'created_at' => '2026-10-19 10:00:00',
            ],
            [
                'zone_id' => 4,
                'zone_name' => 'Zona Jalan Raya Timur',
                'zone_type' => 'jalan_raya',
                'center_lat' => 3.918911,
                'center_lon' => 97.535144,
                'radius_km' => 1.514,
                'polygon_geojson' => null,
                'created_by' => 3,
                'created_at' => '2026-10-19 10:00:00',
            ],
            [
                'zone_id' => 5,
                'zone_name' => 'Zona Hutan Lindung Barat',
                'zone_type' => 'koridor_ekologis',
                'center_lat' => 3.621963,
                'center_lon' => 97.818243,
                'radius_km' => 2.471,
                'polygon_geojson' => null,
                'created_by' => 2,
                'created_at' => '2026-10-19 10:00:00',
            ],
        ];

        foreach ($zones as $item) {
            GeoZone::query()->updateOrCreate(
                ['zone_id' => $item['zone_id']],
                $item,
            );
        }
    }
}