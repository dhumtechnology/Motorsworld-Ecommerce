<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\VehicleModelController;
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

    Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categorias/crear', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categorias/{category}/editar', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::delete('/categorias', [CategoryController::class, 'bulkDestroy'])->name('categories.bulk-destroy');

    Route::get('/marcas', [BrandController::class, 'index'])->name('brands.index');
    Route::get('/marcas/crear', [BrandController::class, 'create'])->name('brands.create');
    Route::post('/marcas', [BrandController::class, 'store'])->name('brands.store');
    Route::get('/marcas/{brand}/editar', [BrandController::class, 'edit'])->name('brands.edit');
    Route::put('/marcas/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('/marcas/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
    Route::delete('/marcas', [BrandController::class, 'bulkDestroy'])->name('brands.bulk-destroy');

    Route::get('/modelos', [VehicleModelController::class, 'index'])->name('models.index');
    Route::get('/modelos/crear', [VehicleModelController::class, 'create'])->name('models.create');
    Route::post('/modelos', [VehicleModelController::class, 'store'])->name('models.store');
    Route::get('/modelos/{vehicleModel}/editar', [VehicleModelController::class, 'edit'])->name('models.edit');
    Route::put('/modelos/{vehicleModel}', [VehicleModelController::class, 'update'])->name('models.update');
    Route::delete('/modelos/{vehicleModel}', [VehicleModelController::class, 'destroy'])->name('models.destroy');
    Route::delete('/modelos', [VehicleModelController::class, 'bulkDestroy'])->name('models.bulk-destroy');
});
