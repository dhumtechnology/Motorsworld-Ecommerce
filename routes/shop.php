<?php

use App\Http\Controllers\Shop\AboutController;
use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la tienda (E-Commerce)
|--------------------------------------------------------------------------
|
| Las vistas Blade se ubicarán en: resources/views/shop/
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/nosotros', [AboutController::class, 'index'])->name('about');

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog');

Route::get('/producto/{product}', [ProductController::class, 'show'])->name('product.show');

/*
|--------------------------------------------------------------------------
| Servicios / reservas de taller
|--------------------------------------------------------------------------
|
| GET  /servicios              shop.services.index
| POST /servicios/reservas     shop.services.store
| GET  /servicios/horarios     shop.services.slots
|
*/
Route::prefix('servicios')->name('services.')->group(function () {
    Route::get('/', [ServiceController::class, 'index'])->name('index');
    Route::post('/reservas', [ServiceController::class, 'store'])->name('store');
    Route::get('/horarios', [ServiceController::class, 'availableSlots'])->name('slots');
});

/*
|--------------------------------------------------------------------------
| Carrito (invitado o autenticado — ver CartResolver y MergeGuestCartAction)
|--------------------------------------------------------------------------
|
| GET    /carrito                                  shop.cart.index
| POST   /carrito/productos/{product}              shop.cart.items.store
| PATCH  /carrito/productos/{product}              shop.cart.items.update
| POST   /carrito/productos/{product}/increment    shop.cart.items.increment
| POST   /carrito/productos/{product}/decrement    shop.cart.items.decrement
|
*/
Route::prefix('carrito')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/productos/{product}', [CartController::class, 'store'])->name('items.store');
    Route::patch('/productos/{product}', [CartController::class, 'update'])->name('items.update');
    Route::post('/productos/{product}/increment', [CartController::class, 'increment'])->name('items.increment');
    Route::post('/productos/{product}/decrement', [CartController::class, 'decrement'])->name('items.decrement');
});

/*
|--------------------------------------------------------------------------
| Checkout + Culqi
|--------------------------------------------------------------------------
|
| Requiere autenticación (orders.user_id obligatorio).
|
| GET  /checkout                  shop.checkout.show
| POST /checkout/pagar            shop.checkout.pay
| GET  /checkout/pedidos/{order}  shop.checkout.orders.show
|
*/
Route::middleware('auth')->prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'show'])->name('show');
    Route::post('/pagar', [CheckoutController::class, 'pay'])->name('pay');
    Route::get('/pedidos/{order}', [CheckoutController::class, 'showOrder'])->name('orders.show');
    Route::post('/pedidos/{order}/simular-pago', [CheckoutController::class, 'simulatePaid'])->name('orders.simulate');
});
