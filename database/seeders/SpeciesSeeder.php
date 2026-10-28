<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $species = [
            [1, 'Orangutan Sumatra', 'Pongo abelii', 'Critically Endangered'],
            [2, 'Harimau Sumatra', 'Panthera tigris sumatrae', 'Critically Endangered'],
            [3, 'Badak Sumatra', 'Dicerorhinus sumatrensis', 'Critically Endangered'],
            [4, 'Gajah Sumatra', 'Elephas maximus sumatranus', 'Critically Endangered'],
            [5, 'Beruang Madu', 'Helarctos malayanus', 'Vulnerable'],
        ];
        foreach ($species as $s) {
            DB::table('species')->updateOrInsert(
                ['species_id' => $s[0]],
                ['common_name' => $s[1], 'scientific_name' => $s[2], 'conservation_status' => $s[3]]
            );
        }
    }
}
