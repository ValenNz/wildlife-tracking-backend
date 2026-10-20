<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = [
            [
                'booking_id' => 1,
                'booking_code' => 'WLT-20261019-001',
                'visitor_id' => 1,
                'visit_date' => '2026-10-25',
                'total_amount' => 125000.00,
                'booking_status' => 'paid',
                'expires_at' => '2026-10-20 23:59:59',
                'created_at' => '2026-10-19 10:00:00',
                'updated_at' => '2026-10-19 10:30:00',
            ],
            [
                'booking_id' => 2,
                'booking_code' => 'WLT-20261019-002',
                'visitor_id' => 2,
                'visit_date' => '2026-10-26',
                'total_amount' => 50000.00,
                'booking_status' => 'paid',
                'expires_at' => '2026-10-20 23:59:59',
                'created_at' => '2026-10-19 10:05:00',
                'updated_at' => '2026-10-19 10:35:00',
            ],
            [
                'booking_id' => 3,
                'booking_code' => 'WLT-20261019-003',
                'visitor_id' => 3,
                'visit_date' => '2026-10-27',
                'total_amount' => 130000.00,
                'booking_status' => 'pending',
                'expires_at' => '2026-10-20 23:59:59',
                'created_at' => '2026-10-19 10:10:00',
                'updated_at' => '2026-10-19 10:10:00',
            ],
            [
                'booking_id' => 4,
                'booking_code' => 'WLT-20261019-004',
                'visitor_id' => 4,
                'visit_date' => '2026-10-28',
                'total_amount' => 200000.00,
                'booking_status' => 'paid',
                'expires_at' => '2026-10-20 23:59:59',
                'created_at' => '2026-10-19 10:15:00',
                'updated_at' => '2026-10-19 10:45:00',
            ],
            [
                'booking_id' => 5,
                'booking_code' => 'WLT-20261019-005',
                'visitor_id' => 5,
                'visit_date' => '2026-10-29',
                'total_amount' => 75000.00,
                'booking_status' => 'cancelled',
                'expires_at' => '2026-10-20 23:59:59',
                'created_at' => '2026-10-19 10:20:00',
                'updated_at' => '2026-10-19 10:25:00',
            ],
        ];

        foreach ($bookings as $booking) {
            Booking::query()->updateOrCreate(
                ['booking_id' => $booking['booking_id']],
                $booking,
            );
        }
    }
}