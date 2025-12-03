<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monitor checks to run every minute
Schedule::command('monitors:schedule-checks')->everyMinute();

// Update scheduler heartbeat every minute to track scheduler status
Schedule::command('scheduler:heartbeat')->everyMinute();

// Prune old check history daily at 2:00 AM
Schedule::command('checks:prune')->dailyAt('02:00');
