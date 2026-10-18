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
                'common_name' => 'Orangutan Sumatra',
                'scientific_name' => 'Pongo abelii',
                'conservation_status' => 'Critically Endangered',
            ],
            [
                'common_name' => 'Harimau Sumatra',
                'scientific_name' => 'Panthera tigris sumatrae',
                'conservation_status' => 'Critically Endangered',
            ],
            [
                'common_name' => 'Badak Sumatra',
                'scientific_name' => 'Dicerorhinus sumatrensis',
                'conservation_status' => 'Critically Endangered',
            ],
            [
                'common_name' => 'Gajah Sumatra',
                'scientific_name' => 'Elephas maximus sumatrensis',
                'conservation_status' => 'Endangered',
            ],
            [
                'common_name' => 'Rusa',
                'scientific_name' => 'Rusa unicolor',
                'conservation_status' => 'Vulnerable',
            ],
        ];

        foreach ($species as $item) {
            Species::query()->updateOrCreate(
                ['scientific_name' => $item['scientific_name']],
                $item,
            );
        }
    }
}
