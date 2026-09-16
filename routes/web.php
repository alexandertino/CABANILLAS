<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('clinica.dashboard');
    }

    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/login', fn () => redirect()->route('login'));

/*
|--------------------------------------------------------------------------
| Rutas del sistema Clínica Cabanillas
|--------------------------------------------------------------------------
*/

require __DIR__.'/clinica.php';