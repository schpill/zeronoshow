<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Observers\ReservationObserver;
use App\Services\Contracts\SmsServiceInterface;
use App\Services\TwilioSmsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsServiceInterface::class, TwilioSmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);
    }
}
