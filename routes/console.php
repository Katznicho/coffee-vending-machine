<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('vending.sync_pending_payments', true)) {
    $schedule = Schedule::command('payments:sync-pending')
        ->withoutOverlapping()
        ->onOneServer();

    match (config('vending.sync_pending_schedule', 'everyMinute')) {
        'everyFiveMinutes' => $schedule->everyFiveMinutes(),
        'everyTenMinutes' => $schedule->everyTenMinutes(),
        default => $schedule->everyMinute(),
    };
}
