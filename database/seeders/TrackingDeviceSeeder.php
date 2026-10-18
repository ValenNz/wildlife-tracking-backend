<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\TrackingDevice;
use Illuminate\Database\Seeder;

class TrackingDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            ['animal_code' => 'ORN-01', 'model' => 'Vectronic Vertex Lite', 'battery_life_months' => 18, 'status' => 'Active'],
            ['animal_code' => 'HRM-01', 'model' => 'Lotek GPS-400', 'battery_life_months' => 24, 'status' => 'Active'],
            ['animal_code' => 'BDK-01', 'model' => 'Vectronic Vertex Plus', 'battery_life_months' => 12, 'status' => 'Active'],
            ['animal_code' => 'GJS-01', 'model' => 'Lotek GPS-500', 'battery_life_months' => 36, 'status' => 'Maintenance'],
            ['animal_code' => 'RSA-01', 'model' => 'Sirtrack Iridium 100', 'battery_life_months' => 15, 'status' => 'Inactive'],
        ];

        foreach ($devices as $item) {
            $animal = Animal::query()
                ->where('animal_code', $item['animal_code'])
                ->firstOrFail();

            TrackingDevice::query()->updateOrCreate(
                ['animal_id' => $animal->animal_id],
                [
                    'model' => $item['model'],
                    'installation_date' => '2024-02-15',
                    'battery_life_months' => $item['battery_life_months'],
                    'status' => $item['status'],
                ],
            );
        }
    }
}
