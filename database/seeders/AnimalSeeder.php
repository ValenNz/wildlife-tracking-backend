<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Species;
use Illuminate\Database\Seeder;

class AnimalSeeder extends Seeder
{
    public function run(): void
    {
        $animals = [
            [
                'scientific_name' => 'Pongo abelii',
                'animal_code' => 'ORN-01',
                'gender' => 'Tidak Diketahui',
                'birth_date' => null,
            ],
            [
                'scientific_name' => 'Panthera tigris sumatrae',
                'animal_code' => 'HRM-01',
                'gender' => 'Jantan',
                'birth_date' => '2017-06-14',
            ],
            [
                'scientific_name' => 'Dicerorhinus sumatrensis',
                'animal_code' => 'BDK-01',
                'gender' => 'Betina',
                'birth_date' => '2019-03-02',
            ],
            [
                'scientific_name' => 'Elephas maximus sumatrensis',
                'animal_code' => 'GJS-01',
                'gender' => 'Jantan',
                'birth_date' => '2012-11-25',
            ],
            [
                'scientific_name' => 'Rusa unicolor',
                'animal_code' => 'RSA-01',
                'gender' => 'Betina',
                'birth_date' => '2021-08-09',
            ],
        ];

        foreach ($animals as $item) {
            $species = Species::query()
                ->where('scientific_name', $item['scientific_name'])
                ->firstOrFail();

            Animal::query()->updateOrCreate(
                ['animal_code' => $item['animal_code']],
                [
                    'species_id' => $species->species_id,
                    'gender' => $item['gender'],
                    'birth_date' => $item['birth_date'],
                    'is_active' => true,
                ],
            );
        }
    }
}
