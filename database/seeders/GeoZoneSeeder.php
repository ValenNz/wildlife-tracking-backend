<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\User;
use Illuminate\Database\Seeder;

class GeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->where('role', 'administrator_sistem')->firstOrFail();

        $zones = [
            [
                'zone_name' => 'Desa Bukit Lawang',
                'zone_type' => 'Permukiman',
                'polygon_data' => [
                    'type' => 'Polygon',
                    'coordinates' => [[[98.1200, 3.5500], [98.1400, 3.5500], [98.1400, 3.5700], [98.1200, 3.5700], [98.1200, 3.5500]]],
                ],
            ],
            [
                'zone_name' => 'Jalan Lintas Sumatera Bukit Lawang-Medan',
                'zone_type' => 'Jalan Raya',
                'polygon_data' => [
                    'type' => 'LineString',
                    'coordinates' => [[97.9500, 3.4800], [98.0500, 3.5200], [98.1500, 3.5600]],
                ],
            ],
            [
                'zone_name' => 'Perkebunan Kelapa Sawit Kuala Batang',
                'zone_type' => 'Perkebunan',
                'polygon_data' => [
                    'type' => 'Polygon',
                    'coordinates' => [[[98.0500, 3.6000], [98.0900, 3.6000], [98.0900, 3.6400], [98.0500, 3.6400], [98.0500, 3.6000]]],
                ],
            ],
            [
                'zone_name' => 'Koridor Ekologis Sungai Batang Gadis',
                'zone_type' => 'Koridor Ekologis',
                'polygon_data' => [
                    'type' => 'Polygon',
                    'coordinates' => [[[97.9000, 3.6500], [97.9800, 3.6500], [97.9800, 3.6900], [97.9000, 3.6900], [97.9000, 3.6500]]],
                ],
            ],
            [
                'zone_name' => 'Desa Sungai Sembilan',
                'zone_type' => 'Permukiman',
                'polygon_data' => [
                    'type' => 'Polygon',
                    'coordinates' => [[[98.0200, 3.3800], [98.0600, 3.3800], [98.0600, 3.4100], [98.0200, 3.4100], [98.0200, 3.3800]]],
                ],
            ],
        ];

        foreach ($zones as $item) {
            GeoZone::query()->updateOrCreate(
                ['zone_name' => $item['zone_name']],
                [
                    'zone_type' => $item['zone_type'],
                    'polygon_data' => $item['polygon_data'],
                    'created_by' => $creator->user_id,
                ],
            );
        }
    }
}
