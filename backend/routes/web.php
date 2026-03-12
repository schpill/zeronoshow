<?php

use App\Http\Controllers\ConfirmationController;
use Illuminate\Support\Facades\Route;

Route::get('/c/{token}', [ConfirmationController::class, 'show'])->name('confirmation.show');
Route::post('/c/{token}/confirm', [ConfirmationController::class, 'confirm'])->name('confirmation.confirm');
