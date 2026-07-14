<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del panel administrativo
|--------------------------------------------------------------------------
|
| Prefijo: /admin
| Nombre de rutas: admin.*
|
| Las vistas Blade se ubicarán en: resources/views/admin/
|
*/

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/productos', [ProductController::class, 'index'])->name('products.index');
});
