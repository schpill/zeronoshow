<?php

use App\Http\Controllers\ConfirmationController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:confirmation')->group(function (): void {
    Route::get('/c/{token}', [ConfirmationController::class, 'show'])->name('confirmation.show');
    Route::get('/c/{token}/cancel', [ConfirmationController::class, 'cancel'])->name('confirmation.cancel');
    Route::post('/c/{token}/confirm', [ConfirmationController::class, 'confirm'])->name('confirmation.confirm');
});
