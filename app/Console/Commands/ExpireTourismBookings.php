<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\Tourism\BookingService;
use Illuminate\Console\Command;

class ExpireTourismBookings extends Command
{
    protected $signature = 'tourism:expire-bookings';

    protected $description = 'Kedaluwarsakan pemesanan belum dibayar dan tandai kunjungan lampau sebagai selesai';

    public function handle(BookingService $bookings): int
    {
        $expired = $bookings->expireStale();
        $completed = Booking::where('status', 'checked_in')->whereDate('visit_date', '<', \App\Services\Tourism\TourismClock::today())->update(['status' => 'completed']);

        $this->info("{$expired} pemesanan kedaluwarsa, {$completed} kunjungan selesai.");

        return self::SUCCESS;
    }
}
