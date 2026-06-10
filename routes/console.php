<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:send-reminders')->dailyAt('09:00');
Schedule::command('courses:send-reminders')->dailyAt('09:10');
Schedule::command('bookings:release-expired-holds')->everyFifteenMinutes();
