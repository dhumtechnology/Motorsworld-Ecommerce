<?php

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

Route::get('/', function () {
    return view('admin.dashboard.index');
})->name('dashboard');
