<?php

namespace Database\Seeders;

use App\Models\GPSReadings;
use App\Models\WeatherData;
use Illuminate\Database\Seeder;

class WeatherDataSeeder extends Seeder
{
    public function run(): void
    {
        $readings = GPSReadings::query()->orderBy('reading_id')->get();

        foreach ($readings as $reading) {
            WeatherData::query()->updateOrCreate(
                ['reading_id' => $reading->reading_id],
                [
                    'temperature' => round(fake('id_ID')->randomFloat(2, 24, 32), 2),
                    'humidity' => round(fake('id_ID')->randomFloat(2, 70, 95), 2),
                    'rainfall' => round(fake('id_ID')->randomFloat(2, 0, 35), 2),
                    'recorded_at' => $reading->recorded_at,
                ],
            );
        }
    }
}
