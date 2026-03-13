<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Observers\ReservationObserver;
use App\Services\Contracts\SmsServiceInterface;
use App\Services\Leo\TelegramChannel;
use App\Services\Leo\TwilioSmsChannel;
use App\Services\StripeService;
use App\Services\TwilioSmsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsServiceInterface::class, TwilioSmsService::class);
        $this->app->singleton(StripeService::class);
        $this->app->singleton(TelegramChannel::class);
        $this->app->singleton(TwilioSmsChannel::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinutes(15, 10)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('reservations', fn (Request $request) => Limit::perMinute(60)->by((string) optional($request->user())->id ?: $request->ip()));
        RateLimiter::for('confirmation', fn (Request $request) => Limit::perMinute(10)->by($request->route('token') ?? $request->ip()));
        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(200)->by($request->ip()));
    }
}
