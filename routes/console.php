<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate today's + tomorrow's daily puzzle just after midnight; the
// puzzle page also self-heals by generating on demand if this misses.
Schedule::command('puzzle:generate --days=2')->dailyAt('00:05');
