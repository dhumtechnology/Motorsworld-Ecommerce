<?php

use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\RegisterCustomerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la tienda (E-Commerce)
|--------------------------------------------------------------------------
|
| Las vistas Blade se ubicarán en: resources/views/shop/
|
*/

Route::get('/tienda', function () {
    return response('Tienda E-Commerce — pendiente de implementación', 200);
})->name('home');

Route::post('/registro', [RegisterCustomerController::class, 'store'])->name('register');

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog');

Route::get('/producto/{product}', [ProductController::class, 'show'])->name('product.show');
