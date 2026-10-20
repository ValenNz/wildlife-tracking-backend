<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Independent tables first
            SpeciesSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            TicketTypeSeeder::class,
            VisitorSeeder::class,
            
            // Dependent on above
            RolePermissionSeeder::class,
            UserSeeder::class,
            AnimalSeeder::class,
            TrackingDeviceSeeder::class,
            GeoZoneSeeder::class,
            
            // Dependent on multiple
            GpsReadingSeeder::class,
            AnimalGeoZoneSeeder::class,
            IncidentReportSeeder::class,
            AiMediaUploadSeeder::class,
            AiPredictionSeeder::class,
            WeatherDataSeeder::class,
            NotificationSeeder::class,
            
            // Booking system
            BookingSeeder::class,
            BookingItemSeeder::class,
            PaymentSeeder::class,
            
            // Reports and logs
            AggregateReportSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}