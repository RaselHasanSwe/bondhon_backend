<?php

namespace App\Providers;

use App\Events\InterestReceived;
use App\Jobs\ExpireOldInterests;
use App\Jobs\SendDailyMatchDigest;
use App\Listeners\SendInterestNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // Register event → listener bindings
        Event::listen(InterestReceived::class, SendInterestNotification::class);

        // Schedule recurring jobs
        Schedule::job(new ExpireOldInterests())->dailyAt('00:00');
        Schedule::job(new SendDailyMatchDigest())->dailyAt('08:00');
    }
}
