<?php

namespace App\Providers;

use App\Events\InterestReceived;
use App\Jobs\ExpireOldInterests;
use App\Jobs\SendDailyMatchDigest;
use App\Listeners\SendInterestNotification;
use App\Services\SiteSettingService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;


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
        Event::listen(InterestReceived::class, SendInterestNotification::class);

        Schedule::job(new ExpireOldInterests())->dailyAt('00:00');
        Schedule::job(new SendDailyMatchDigest())->dailyAt('08:00');

        View::composer(['admin.layout', 'admin.login'], function ($view) {
            $settings = app(SiteSettingService::class);

            $view->with([
                'siteName'     => $settings->get('site_name', config('app.name', 'Bondhon')),
                'siteLogo'     => $settings->get('site_logo'),
                'siteFavicon'  => $settings->get('site_favicon'),
            ]);
        });

        View::composer('emails.*', function ($view) {
            $settings = app(SiteSettingService::class);

            $view->with([
                'siteName'        => $settings->get('site_name', config('app.name', 'Bondhon')),
                'siteSlogan'      => $settings->get('site_slogan', ''),
                'contactEmail'    => $settings->get('contact_email', ''),
                'contactPhone'    => $settings->get('contact_phone', ''),
                'contactAddress'  => $settings->get('contact_address', ''),
                'socialLinks'     => $settings->socialLinks(),
            ]);
        });
    }
}
