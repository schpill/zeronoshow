<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerCrmController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LeoAddonController;
use App\Http\Controllers\Api\LeoChannelController;
use App\Http\Controllers\Api\LeoWhatsAppCreditController;
use App\Http\Controllers\Api\PublicBookingController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\ReviewRequestController;
use App\Http\Controllers\Api\ReviewSettingsController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\VoiceCallController;
use App\Http\Controllers\Api\VoiceCreditController;
use App\Http\Controllers\Api\VoiceSettingsController;
use App\Http\Controllers\Api\WaitlistController;
use App\Http\Controllers\Api\WaitlistSettingsController;
use App\Http\Controllers\Api\WidgetSettingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Webhook\LeoWebhookController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use App\Http\Controllers\Webhook\TwilioWebhookController;
use App\Http\Controllers\Webhook\VoiceGatherController;
use App\Http\Controllers\Webhook\VoiceStatusController;
use App\Http\Controllers\Webhook\VoiceTwimlController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Public widget routes (unauthenticated)
    Route::prefix('public/widget/{business:public_token}')->middleware('widget.allowframe')->group(function (): void {
        Route::get('/config', [PublicBookingController::class, 'config']);
        Route::get('/slots', [PublicBookingController::class, 'slots']);
        Route::post('/otp/send', [PublicBookingController::class, 'sendOtp'])->middleware('throttle:widget');
        Route::post('/otp/verify', [PublicBookingController::class, 'verifyOtp']);
        Route::post('/reservations', [PublicBookingController::class, 'store']);
    });

    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/health', [HealthController::class, 'check']);
    Route::post('/webhooks/twilio', [TwilioWebhookController::class, 'handle'])->middleware('throttle:webhook');
    Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);
    Route::post('/webhooks/leo/telegram', [LeoWebhookController::class, 'telegram'])->middleware(['telegram.allowlist', 'throttle:webhook']);
    Route::get('/webhooks/leo/whatsapp', [LeoWebhookController::class, 'whatsapp'])->middleware('throttle:webhook');
    Route::post('/webhooks/leo/whatsapp', [LeoWebhookController::class, 'whatsapp'])->middleware('throttle:webhook');
    Route::get('/webhooks/voice/twiml/{voiceCallLog}', [VoiceTwimlController::class, 'twiml'])->name('voice.twiml')->middleware('throttle:webhook');
    Route::post('/webhooks/voice/gather/{voiceCallLog}', [VoiceGatherController::class, 'gather'])->name('voice.gather')->middleware('throttle:webhook');
    Route::post('/webhooks/voice/status/{voiceCallLog}', [VoiceStatusController::class, 'status'])->name('voice.status')->middleware('throttle:webhook');
    Route::get('/webhooks/leo/voice/twiml/{voiceCallLog}', [VoiceTwimlController::class, 'twiml'])->middleware('throttle:webhook');
    Route::post('/webhooks/leo/voice/gather/{voiceCallLog}', [VoiceGatherController::class, 'gather'])->middleware('throttle:webhook');
    Route::post('/webhooks/leo/voice/status/{voiceCallLog}', [VoiceStatusController::class, 'status'])->middleware('throttle:webhook');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/leo/channels', [LeoChannelController::class, 'index']);
        Route::get('/leo/addon-status', [LeoAddonController::class, 'status']);
        Route::post('/leo/addon/activate', [LeoAddonController::class, 'activate']);
        Route::post('/leo/addon/deactivate', [LeoAddonController::class, 'deactivate']);

        Route::get('/leo/whatsapp/credits', [LeoWhatsAppCreditController::class, 'status']);
        Route::post('/leo/whatsapp/credits/topup', [LeoWhatsAppCreditController::class, 'topup']);
        Route::patch('/leo/whatsapp/credits/cap', [LeoWhatsAppCreditController::class, 'setCap']);
        Route::get('/voice/credits', [VoiceCreditController::class, 'status']);
        Route::post('/voice/credits/topup', [VoiceCreditController::class, 'topup']);
        Route::patch('/voice/credits/cap', [VoiceCreditController::class, 'setCap']);
        Route::get('/voice/settings', [VoiceSettingsController::class, 'show']);
        Route::patch('/voice/settings', [VoiceSettingsController::class, 'update']);

        Route::get('/subscription', [SubscriptionController::class, 'show']);
        Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout']);
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/lookup', [CustomerController::class, 'lookup']);
        Route::patch('/customers/{customer}/crm', [CustomerCrmController::class, 'update']);
        Route::get('/review-requests', [ReviewRequestController::class, 'index']);
        Route::get('/review-requests/stats', [ReviewRequestController::class, 'stats']);
        Route::get('/review-settings', [ReviewSettingsController::class, 'show']);
        Route::patch('/review-settings', [ReviewSettingsController::class, 'update']);
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
        Route::get('/reservations/{reservation}/calls', [VoiceCallController::class, 'logs']);
        Route::post('/reservations/{reservation}/call', [VoiceCallController::class, 'initiate']);
        Route::post('/reservations/{reservation}/voice-call', [VoiceCallController::class, 'queue']);
        Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);

        // Authenticated widget settings routes
        Route::get('/businesses/{business}/widget', [WidgetSettingController::class, 'show']);
        Route::patch('/businesses/{business}/widget', [WidgetSettingController::class, 'update']);
        Route::get('/businesses/{business}/widget/stats', [WidgetSettingController::class, 'stats']);

        Route::prefix('waitlist')->group(function (): void {
            Route::get('/', [WaitlistController::class, 'index']);
            Route::post('/', [WaitlistController::class, 'store']);
            Route::delete('/{entry}', [WaitlistController::class, 'destroy']);
            Route::post('/reorder', [WaitlistController::class, 'reorder']);
            Route::post('/{entry}/notify', [WaitlistController::class, 'notify']);
            Route::get('/settings', [WaitlistSettingsController::class, 'show']);
            Route::patch('/settings', [WaitlistSettingsController::class, 'update']);
            Route::post('/settings/regenerate-link', [WaitlistSettingsController::class, 'regenerateLink']);
        });

        Route::middleware('subscription')->group(function (): void {
            Route::post('/reservations', [ReservationController::class, 'store'])->middleware('throttle:reservations');
            Route::middleware('leo.addon')->group(function (): void {
                Route::post('/leo/channels', [LeoChannelController::class, 'store']);
                Route::patch('/leo/channels/{leoChannel}', [LeoChannelController::class, 'update']);
            });
            Route::delete('/leo/channels/{leoChannel}', [LeoChannelController::class, 'destroy']);
        });
    });
});
