<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeatherDataSeeder extends Seeder
{
    public function run(): void
    {
        $weather = [
            [1, 1, 3.658620, 97.731964, '2026-10-19 10:00:00', 28.50, 0.00, 75.00, 1012.00, 'Open-Meteo'],
            [2, 2, 3.967782, 97.381211, '2026-10-19 09:30:00', 27.80, 2.50, 82.00, 1011.50, 'Open-Meteo'],
            [3, 3, 3.730805, 97.613118, '2026-10-19 11:15:00', 29.20, 0.00, 70.00, 1013.00, 'Open-Meteo'],
            [4, 4, 3.756809, 97.450071, '2026-10-18 15:20:00', 26.90, 5.80, 88.00, 1010.80, 'Open-Meteo'],
            [5, 5, 3.899724, 97.045250, '2026-10-15 08:45:00', 25.50, 12.30, 92.00, 1009.50, 'Open-Meteo'],
        ];
        foreach ($weather as $w) {
            DB::table('weather_data')->updateOrInsert(
                ['weather_id' => $w[0]],
                ['reading_id' => $w[1], 'latitude' => $w[2], 'longitude' => $w[3], 'observed_at' => $w[4], 'temperature_c' => $w[5], 'rainfall_mm' => $w[6], 'humidity_percent' => $w[7], 'pressure_hpa' => $w[8], 'source' => $w[9]]
            );
        }
    }
}
