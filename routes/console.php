<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tourism:expire-bookings')->everyFiveMinutes();

Schedule::command('tokens:prune-idle')->hourly();

Schedule::command('tracking:simulate-gps')
    ->everyTenSeconds()
    ->withoutOverlapping()
    ->when(fn () => (bool) config('services.gps_simulator.enabled'));
