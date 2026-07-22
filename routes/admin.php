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
    Route::get('/productos/crear', [ProductController::class, 'create'])->name('products.create');
    Route::post('/productos', [ProductController::class, 'store'])->name('products.store');
    Route::get('/productos/{product}/editar', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/productos/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/productos/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::delete('/productos', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
});
