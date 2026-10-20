<?php

namespace Database\Seeders;

use App\Models\BookingItem;
use Illuminate\Database\Seeder;

class BookingItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'item_id' => 1,
                'booking_id' => 1,
                'ticket_type_id' => 1,
                'quantity' => 2,
                'unit_price' => 50000.00,
            ],
            [
                'item_id' => 2,
                'booking_id' => 1,
                'ticket_type_id' => 2,
                'quantity' => 1,
                'unit_price' => 25000.00,
            ],
            [
                'item_id' => 3,
                'booking_id' => 2,
                'ticket_type_id' => 1,
                'quantity' => 1,
                'unit_price' => 50000.00,
            ],
            [
                'item_id' => 4,
                'booking_id' => 3,
                'ticket_type_id' => 1,
                'quantity' => 2,
                'unit_price' => 50000.00,
            ],
            [
                'item_id' => 5,
                'booking_id' => 3,
                'ticket_type_id' => 3,
                'quantity' => 1,
                'unit_price' => 30000.00,
            ],
            [
                'item_id' => 6,
                'booking_id' => 4,
                'ticket_type_id' => 5,
                'quantity' => 2,
                'unit_price' => 100000.00,
            ],
            [
                'item_id' => 7,
                'booking_id' => 5,
                'ticket_type_id' => 1,
                'quantity' => 1,
                'unit_price' => 50000.00,
            ],
            [
                'item_id' => 8,
                'booking_id' => 5,
                'ticket_type_id' => 2,
                'quantity' => 1,
                'unit_price' => 25000.00,
            ],
        ];

        foreach ($items as $item) {
            BookingItem::query()->updateOrCreate(
                ['item_id' => $item['item_id']],
                $item,
            );
        }
    }
}