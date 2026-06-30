<?php

use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\ProductController;
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

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog');

Route::get('/producto/{product}', [ProductController::class, 'show'])->name('product.show');

/*
|--------------------------------------------------------------------------
| Carrito (invitado o autenticado — ver CartResolver y MergeGuestCartAction)
|--------------------------------------------------------------------------
|
| POST   /carrito/productos/{product}              shop.cart.items.store
| PATCH  /carrito/productos/{product}              shop.cart.items.update
| POST   /carrito/productos/{product}/increment    shop.cart.items.increment
| POST   /carrito/productos/{product}/decrement    shop.cart.items.decrement
|
*/
Route::prefix('carrito')->name('cart.')->group(function () {
    Route::post('/productos/{product}', [CartController::class, 'store'])->name('items.store');
    Route::patch('/productos/{product}', [CartController::class, 'update'])->name('items.update');
    Route::post('/productos/{product}/increment', [CartController::class, 'increment'])->name('items.increment');
    Route::post('/productos/{product}/decrement', [CartController::class, 'decrement'])->name('items.decrement');
});
