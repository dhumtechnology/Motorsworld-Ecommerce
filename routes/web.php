<?php

use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return response()->noContent();
})->name('health');

Route::get('/', function () {
    return response('Motosworld E-Commerce — en construcción', 200);
})->name('home');
