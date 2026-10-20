<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $species = [
            [
                'species_id' => 1,
                'common_name' => 'Orangutan Sumatra',
                'scientific_name' => 'Pongo abelii',
                'conservation_status' => 'Critically Endangered',
            ],
            [
                'species_id' => 2,
                'common_name' => 'Harimau Sumatra',
                'scientific_name' => 'Panthera tigris sumatrae',
                'conservation_status' => 'Critically Endangered',
            ],
            [
                'species_id' => 3,
                'common_name' => 'Badak Sumatra',
                'scientific_name' => 'Dicerorhinus sumatrensis',
                'conservation_status' => 'Critically Endangered',
            ],
            [
                'species_id' => 4,
                'common_name' => 'Gajah Sumatra',
                'scientific_name' => 'Elephas maximus sumatranus',
                'conservation_status' => 'Critically Endangered',
            ],
            [
                'species_id' => 5,
                'common_name' => 'Beruang Madu',
                'scientific_name' => 'Helarctos malayanus',
                'conservation_status' => 'Vulnerable',
            ],
        ];

        foreach ($species as $item) {
            Species::query()->updateOrCreate(
                ['species_id' => $item['species_id']],
                $item,
            );
        }
    }
}