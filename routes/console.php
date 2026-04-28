<?php

use App\Jobs\ExpireOldInterests;
use App\Jobs\SendDailyMatchDigest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run daily match digest every day at 8:00 AM
Schedule::job(new SendDailyMatchDigest)->dailyAt('08:00');

// Expire old pending interests every day at midnight
Schedule::job(new ExpireOldInterests)->dailyAt('00:00');
