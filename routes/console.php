<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires the server's cron to call `php artisan schedule:run` every
// minute (standard Laravel deploy step) — see Website Health's "Sao lưu"
// group for whether this is actually running in practice.
Schedule::command('backup:run')->dailyAt('02:00');
