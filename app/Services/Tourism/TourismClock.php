<?php

namespace App\Services\Tourism;

use Illuminate\Support\Carbon;

class TourismClock
{
    public static function timezone(): string
    {
        return (string) config('services.tourism.timezone', 'Asia/Jakarta');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function today(): string
    {
        return self::now()->toDateString();
    }

    public static function at(string $date, string $time): Carbon
    {
        return Carbon::parse($date.' '.substr($time, 0, 5), self::timezone());
    }
}
