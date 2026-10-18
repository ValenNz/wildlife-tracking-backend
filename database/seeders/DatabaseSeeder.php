<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SpeciesSeeder::class,
            UserSeeder::class,
            AnimalSeeder::class,
            TrackingDeviceSeeder::class,
            GeoZoneSeeder::class,
            GpsReadingSeeder::class,
            AnimalGeoZoneSeeder::class,
            IncidentReportSeeder::class,
            AiMediaUploadSeeder::class,
            AiPredictionSeeder::class,
            WeatherDataSeeder::class,
            NotificationSeeder::class,
            ActivityLogSeeder::class,
            AggregateReportSeeder::class,
        ]);
    }
}
