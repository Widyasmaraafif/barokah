<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire overdue pending_payment orders every minute and restore
// reserved stock (spec §14.5). Overlap is prevented so two scheduler
// runs never expire the same order concurrently.
Schedule::command('orders:expire-pending')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer();
