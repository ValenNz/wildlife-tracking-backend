<?php

namespace Database\Seeders;

use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        $ticketTypes = [
            [
                'ticket_type_id' => 1,
                'type_name' => 'Dewasa',
                'description' => 'Tiket masuk untuk usia 13 tahun ke atas',
                'price' => 50000.00,
                'is_active' => true,
                'created_at' => '2026-10-19 10:00:00',
            ],
            [
                'ticket_type_id' => 2,
                'type_name' => 'Anak-anak',
                'description' => 'Tiket masuk untuk usia 3-12 tahun',
                'price' => 25000.00,
                'is_active' => true,
                'created_at' => '2026-10-19 10:00:00',
            ],
            [
                'ticket_type_id' => 3,
                'type_name' => 'Lansia',
                'description' => 'Tiket masuk untuk usia di atas 60 tahun',
                'price' => 30000.00,
                'is_active' => true,
                'created_at' => '2026-10-19 10:00:00',
            ],
            [
                'ticket_type_id' => 4,
                'type_name' => 'Mahasiswa',
                'description' => 'Tiket masuk dengan menunjukkan KTM aktif',
                'price' => 35000.00,
                'is_active' => true,
                'created_at' => '2026-10-19 10:00:00',
            ],
            [
                'ticket_type_id' => 5,
                'type_name' => 'Turis Asing',
                'description' => 'Tiket masuk untuk wisatawan mancanegara',
                'price' => 100000.00,
                'is_active' => true,
                'created_at' => '2026-10-19 10:00:00',
            ],
        ];

        foreach ($ticketTypes as $type) {
            TicketType::query()->updateOrCreate(
                ['ticket_type_id' => $type['ticket_type_id']],
                $type,
            );
        }
    }
}