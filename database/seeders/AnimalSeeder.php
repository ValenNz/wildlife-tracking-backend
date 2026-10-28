<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalSeeder extends Seeder
{
    public function run(): void
    {
        $animals = [
            [1, 1, 'SATWA-742', '2023-08-04', 'male', 1],
            [2, 2, 'SATWA-281', '2019-12-28', 'unknown', 1],
            [3, 3, 'SATWA-803', '2019-11-21', 'female', 1],
            [4, 4, 'SATWA-387', '2022-03-18', 'female', 1],
            [5, 5, 'SATWA-968', '2014-02-14', 'female', 1],
        ];
        foreach ($animals as $a) {
            DB::table('animals')->updateOrInsert(
                ['animal_id' => $a[0]],
                ['species_id' => $a[1], 'name' => $a[2], 'birth_date' => $a[3], 'gender' => $a[4], 'is_active' => $a[5]]
            );
        }
    }
}
