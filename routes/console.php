<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('exchange-rates:fetch')
    ->dailyAt('00:00')
    ->timezone('America/Lima')
    ->withoutOverlapping();

Schedule::command('exchange-rates:fetch')
    ->dailyAt('12:00')
    ->timezone('America/Lima')
    ->withoutOverlapping();
