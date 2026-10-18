<?php

namespace Database\Seeders;

use App\Models\GPSReadings;
use App\Models\TrackingDevice;
use Illuminate\Database\Seeder;

class GpsReadingSeeder extends Seeder
{
    public function run(): void
    {
        $trajectories = [
            ['lat' => 3.5512, 'lon' => 98.1234],
            ['lat' => 3.6018, 'lon' => 97.9562],
            ['lat' => 3.4785, 'lon' => 98.2011],
            ['lat' => 3.6723, 'lon' => 97.8894],
            ['lat' => 3.3947, 'lon' => 98.0456],
        ];

        foreach (TrackingDevice::query()->orderBy('device_id')->get() as $index => $device) {
            $origin = $trajectories[$index % count($trajectories)];

            for ($step = 0; $step < 5; $step++) {
                $latitude = $origin['lat'] + ($step * 0.0042) - 0.0084;
                $longitude = $origin['lon'] + ($step * 0.0037) - 0.0074;

                $recordedAt = now()->subDays(4 - $step)->setTime(6 + $step, 15, 0);

                GPSReadings::query()->updateOrCreate(
                    [
                        'device_id' => $device->device_id,
                        'recorded_at' => $recordedAt->toDateTimeString(),
                    ],
                    [
                        'latitude' => round($latitude, 8),
                        'longitude' => round($longitude, 8),
                        'accuracy_meters' => round(fake('id_ID')->randomFloat(2, 3, 25), 2),
                    ],
                );
            }
        }
    }
}
