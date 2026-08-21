<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Shop\RegisterCustomerController;
use App\Http\Controllers\Webhooks\CulqiWebhookController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/up', function () {
    return response()->noContent();
})->name('health');

/*
|--------------------------------------------------------------------------
| Fallback /storage sin symlink (hosting public_html + laravel)
|--------------------------------------------------------------------------
*/
Route::get('/storage/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);

    $candidates = array_unique(array_filter([
        Storage::disk('public')->path($path),
        storage_path('app/public'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path)),
        dirname(base_path()).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path),
    ]));

    foreach ($candidates as $fullPath) {
        if (is_file($fullPath)) {
            return response()->file($fullPath);
        }
    }

    abort(404);
})->where('path', '.*')->name('storage.public');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterCustomerController::class, 'create'])->name('register');
    Route::post('/register', [RegisterCustomerController::class, 'store'])->name('register.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::post('/webhooks/culqi', CulqiWebhookController::class)
    ->name('webhooks.culqi');

Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->name('webhooks.mercadopago');

Route::get('/webhooks/mercadopago', MercadoPagoWebhookController::class);
