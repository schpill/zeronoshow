<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use App\Http\Controllers\Webhook\TwilioWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/webhooks/twilio', [TwilioWebhookController::class, 'handle']);
    Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/subscription', [SubscriptionController::class, 'show']);
        Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout']);
        Route::get('/customers/lookup', [CustomerController::class, 'lookup']);
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
        Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);

        Route::middleware('subscription')->group(function (): void {
            Route::post('/reservations', [ReservationController::class, 'store']);
        });
    });
});
