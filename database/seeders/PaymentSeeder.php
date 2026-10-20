<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $payments = [
            [
                'payment_id' => 1,
                'booking_id' => 1,
                'payment_method' => 'qris',
                'amount' => 125000.00,
                'payment_status' => 'success',
                'transaction_ref' => 'QRIS-20261019-001',
                'paid_at' => '2026-10-19 10:30:00',
                'created_at' => '2026-10-19 10:30:00',
            ],
            [
                'payment_id' => 2,
                'booking_id' => 2,
                'payment_method' => 'transfer_bank',
                'amount' => 50000.00,
                'payment_status' => 'success',
                'transaction_ref' => 'TRF-20261019-002',
                'paid_at' => '2026-10-19 10:35:00',
                'created_at' => '2026-10-19 10:35:00',
            ],
            [
                'payment_id' => 3,
                'booking_id' => 3,
                'payment_method' => 'e_wallet',
                'amount' => 130000.00,
                'payment_status' => 'pending',
                'transaction_ref' => null,
                'paid_at' => null,
                'created_at' => '2026-10-19 10:40:00',
            ],
            [
                'payment_id' => 4,
                'booking_id' => 4,
                'payment_method' => 'kartu_kredit',
                'amount' => 200000.00,
                'payment_status' => 'success',
                'transaction_ref' => 'CC-20261019-004',
                'paid_at' => '2026-10-19 10:45:00',
                'created_at' => '2026-10-19 10:45:00',
            ],
            [
                'payment_id' => 5,
                'booking_id' => 5,
                'payment_method' => 'qris',
                'amount' => 75000.00,
                'payment_status' => 'expired',
                'transaction_ref' => 'QRIS-20261019-005',
                'paid_at' => null,
                'created_at' => '2026-10-19 10:50:00',
            ],
        ];

        foreach ($payments as $payment) {
            Payment::query()->updateOrCreate(
                ['payment_id' => $payment['payment_id']],
                $payment,
            );
        }
    }
}