<?php

namespace App\Providers;

use App\Events\LeoWhatsAppCreditExhaustedEvent;
use App\Events\LeoWhatsAppLowBalanceEvent;
use App\Events\VoiceCreditExhaustedEvent;
use App\Events\VoiceLowBalanceEvent;
use App\Leo\Tools\LeoWhatsAppConversationTracker;
use App\Leo\Tools\LeoWhatsAppCreditService;
use App\Leo\Tools\TelegramChannel;
use App\Leo\Tools\TwilioSmsChannel;
use App\Leo\Tools\WhatsAppChannel;
use App\Listeners\SendCreditExhaustedNotification;
use App\Listeners\SendLowBalanceNotification;
use App\Listeners\SendVoiceCreditExhaustedNotification;
use App\Listeners\SendVoiceLowBalanceNotification;
use App\Models\Reservation;
use App\Models\WaitlistEntry;
use App\Observers\ReservationObserver;
use App\Policies\WaitlistPolicy;
use App\Services\Contracts\SmsServiceInterface;
use App\Services\StripeService;
use App\Services\TwilioSmsService;
use App\Services\VoiceCreditService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        $this->app->singleton(WhatsAppChannel::class);
        $this->app->singleton(LeoWhatsAppCreditService::class);
        $this->app->singleton(LeoWhatsAppConversationTracker::class);
        $this->app->singleton(VoiceCreditService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(WaitlistEntry::class, WaitlistPolicy::class);

        Reservation::observe(ReservationObserver::class);

        Event::listen(
            LeoWhatsAppCreditExhaustedEvent::class,
            SendCreditExhaustedNotification::class
        );

        Event::listen(
            LeoWhatsAppLowBalanceEvent::class,
            SendLowBalanceNotification::class
        );

        Event::listen(
            VoiceCreditExhaustedEvent::class,
            SendVoiceCreditExhaustedNotification::class
        );

        Event::listen(
            VoiceLowBalanceEvent::class,
            SendVoiceLowBalanceNotification::class
        );

        RateLimiter::for('login', fn (Request $request) => Limit::perMinutes(15, 10)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('reservations', fn (Request $request) => Limit::perMinute(60)->by((string) optional($request->user())->id ?: $request->ip()));
        RateLimiter::for('confirmation', fn (Request $request) => Limit::perMinute(10)->by($request->route('token') ?? $request->ip()));
        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(200)->by($request->ip()));
    }
}
