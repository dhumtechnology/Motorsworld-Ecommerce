<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Webhooks\CulqiWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return response()->noContent();
})->name('health');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::post('/webhooks/culqi', CulqiWebhookController::class)
    ->name('webhooks.culqi');
