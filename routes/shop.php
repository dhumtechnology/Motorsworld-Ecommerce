<?php

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
