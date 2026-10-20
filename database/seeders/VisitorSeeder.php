<?php

namespace Database\Seeders;

use App\Models\Visitor;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    public function run(): void
    {
        $visitors = [
            [
                'visitor_id' => 1,
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@gmail.com',
                'phone' => '081234567890',
                'created_at' => '2026-10-19 10:00:00',
            ],
            [
                'visitor_id' => 2,
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@yahoo.com',
                'phone' => '081298765432',
                'created_at' => '2026-10-19 10:05:00',
            ],
            [
                'visitor_id' => 3,
                'name' => 'Rizky Pratama',
                'email' => 'rizky.p@outlook.com',
                'phone' => '081311223344',
                'created_at' => '2026-10-19 10:10:00',
            ],
            [
                'visitor_id' => 4,
                'name' => 'Dewi Lestari',
                'email' => 'dewi.lestari@gmail.com',
                'phone' => '085677889900',
                'created_at' => '2026-10-19 10:15:00',
            ],
            [
                'visitor_id' => 5,
                'name' => 'John Doe',
                'email' => 'john.doe@tourist.com',
                'phone' => '081200001111',
                'created_at' => '2026-10-19 10:20:00',
            ],
        ];

        foreach ($visitors as $visitor) {
            Visitor::query()->updateOrCreate(
                ['visitor_id' => $visitor['visitor_id']],
                $visitor,
            );
        }
    }
}