<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\AnimalGeoZone;
use App\Models\GeoZone;
use App\Models\GPSReadings;
use Illuminate\Database\Seeder;

class AnimalGeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $animals = Animal::query()->orderBy('animal_id')->get();
        $zones = GeoZone::query()->orderBy('zone_id')->get();

        foreach ($animals as $index => $animal) {
            $readings = GPSReadings::query()
                ->whereHas('device', fn ($query) => $query->where('animal_id', $animal->animal_id))
                ->orderBy('recorded_at')
                ->get();

            if ($readings->count() < 2) {
                continue;
            }

            $entry = $readings->first();
            $exit = $readings->last();
            $zone = $zones[$index % $zones->count()];

            AnimalGeoZone::query()->updateOrCreate(
                [
                    'animal_id' => $animal->animal_id,
                    'zone_id' => $zone->zone_id,
                    'entry_reading_id' => $entry->reading_id,
                ],
                [
                    'exit_reading_id' => $exit->reading_id,
                    'duration_hours' => round(
                        abs($exit->recorded_at->diffInMinutes($entry->recorded_at)) / 60,
                        2,
                    ),
                ],
            );
        }
    }
}
