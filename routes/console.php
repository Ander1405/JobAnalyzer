<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Optional: uncomment to run the full JobHunter pipeline daily at 7am (Bogota time).
// Requires `php artisan schedule:work` (or a system cron calling `schedule:run`) to stay running locally.
// Schedule::command('jobs:run')->dailyAt('07:00')->timezone('America/Bogota');
