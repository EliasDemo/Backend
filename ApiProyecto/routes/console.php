<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// (archivo viene con esto por defecto)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 👇 Programa tu comando cada minuto
Schedule::command('vm:tick')->everyMinute();

// (Opcional) según entorno:
// if (app()->isProduction()) {
//     Schedule::command('vm:tick')->everyMinute();
// }
