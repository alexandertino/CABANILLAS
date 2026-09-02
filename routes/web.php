<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')
    ->name('inicio');

/*
|--------------------------------------------------------------------------
| Rutas del sistema Clínica Cabanillas
|--------------------------------------------------------------------------
*/

require __DIR__.'/clinica.php';