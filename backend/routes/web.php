<?php

use App\Http\Controllers\ConfirmationController;
use App\Http\Controllers\Public\PublicWaitlistController;
use App\Http\Controllers\Public\ReviewRedirectController;
use App\Http\Controllers\Public\WaitlistConfirmController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:confirmation')->group(function (): void {
    Route::get('/c/{token}', [ConfirmationController::class, 'show'])->name('confirmation.show');
    Route::get('/c/{token}/cancel', [ConfirmationController::class, 'cancel'])->name('confirmation.cancel');
    Route::post('/c/{token}/confirm', [ConfirmationController::class, 'confirm'])->name('confirmation.confirm');

    Route::get('/waitlist/confirm/{token}', [WaitlistConfirmController::class, 'confirm'])->name('waitlist.confirm');
    Route::get('/waitlist/decline/{token}', [WaitlistConfirmController::class, 'decline'])->name('waitlist.decline');
});

Route::get('/join/{token}', [PublicWaitlistController::class, 'show'])->name('waitlist.join.show');
Route::post('/join/{token}', [PublicWaitlistController::class, 'store'])->name('waitlist.join.store')->middleware('throttle:register');
Route::get('/r/{shortCode}', [ReviewRedirectController::class, 'redirect'])->name('review.redirect');
