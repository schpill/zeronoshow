<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LeoAddonController;
use App\Http\Controllers\Api\LeoChannelController;
use App\Http\Controllers\Api\LeoWhatsAppCreditController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Webhook\LeoWebhookController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use App\Http\Controllers\Webhook\TwilioWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/health', [HealthController::class, 'check']);
    Route::post('/webhooks/twilio', [TwilioWebhookController::class, 'handle'])->middleware('throttle:webhook');
    Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);
    Route::post('/webhooks/leo/telegram', [LeoWebhookController::class, 'telegram'])->middleware(['telegram.allowlist', 'throttle:webhook']);
    Route::get('/webhooks/leo/whatsapp', [LeoWebhookController::class, 'whatsapp'])->middleware('throttle:webhook');
    Route::post('/webhooks/leo/whatsapp', [LeoWebhookController::class, 'whatsapp'])->middleware('throttle:webhook');

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

        Route::get('/subscription', [SubscriptionController::class, 'show']);
        Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout']);
        Route::get('/customers/lookup', [CustomerController::class, 'lookup']);
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
        Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);

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
