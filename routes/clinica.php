<?php

use App\Http\Controllers\Clinica\CitaController;
use App\Http\Controllers\Clinica\ConsultorioController;
use App\Http\Controllers\Clinica\EstadoCitaController;
use App\Http\Controllers\Clinica\MetodoPagoController;
use App\Http\Controllers\Clinica\PacienteController;
use App\Http\Controllers\Clinica\PagoController;
use App\Http\Controllers\Clinica\ProfesionalController;
use App\Http\Controllers\Clinica\ServicioController;
use App\Http\Controllers\Clinica\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('clinica')
    ->name('clinica.')
    ->group(function () {

        Route::get(
            '/',
            [DashboardController::class, 'index']
        )->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Pacientes
        |--------------------------------------------------------------------------
        */
        Route::get(
            'servicios/buscar',
            [ServicioController::class, 'buscar']
        )->name('servicios.buscar');
        
        Route::get(
            'pacientes/buscar',
            [PacienteController::class, 'buscar']
        )->name('pacientes.buscar');

        Route::resource(
            'pacientes',
            PacienteController::class
        )->only([
            'index',
            'store',
            'update',
        ]);

        Route::patch(
            'pacientes/{paciente}/desactivar',
            [PacienteController::class, 'desactivar']
        )->name('pacientes.desactivar');

        Route::patch(
            'pacientes/{paciente}/reactivar',
            [PacienteController::class, 'reactivar']
        )->name('pacientes.reactivar');

        /*
        |--------------------------------------------------------------------------
        | Profesionales
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'profesionales',
            ProfesionalController::class
        )->only([
            'index',
            'store',
            'update',
        ]);

        Route::patch(
            'profesionales/{profesional}/desactivar',
            [ProfesionalController::class, 'desactivar']
        )->name('profesionales.desactivar');

        Route::patch(
            'profesionales/{profesional}/reactivar',
            [ProfesionalController::class, 'reactivar']
        )->name('profesionales.reactivar');

        /*
        |--------------------------------------------------------------------------
        | Consultorios
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'consultorios',
            ConsultorioController::class
        )->only([
            'index',
            'store',
            'update',
        ]);

        Route::patch(
            'consultorios/{consultorio}/desactivar',
            [ConsultorioController::class, 'desactivar']
        )->name('consultorios.desactivar');

        Route::patch(
            'consultorios/{consultorio}/reactivar',
            [ConsultorioController::class, 'reactivar']
        )->name('consultorios.reactivar');

        /*
        |--------------------------------------------------------------------------
        | Servicios
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'servicios',
            ServicioController::class
        )->only([
            'index',
            'store',
            'update',
        ]);

        Route::patch(
            'servicios/{servicio}/desactivar',
            [ServicioController::class, 'desactivar']
        )->name('servicios.desactivar');

        Route::patch(
            'servicios/{servicio}/reactivar',
            [ServicioController::class, 'reactivar']
        )->name('servicios.reactivar');

        /*
        |--------------------------------------------------------------------------
        | Citas
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'citas',
            CitaController::class
        )->only([
            'index',
            'store',
            'update',
        ]);

        Route::patch(
            'citas/{cita}/cancelar',
            [CitaController::class, 'cancelar']
        )->name('citas.cancelar');

        Route::patch(
            'citas/{cita}/estado',
            [CitaController::class, 'cambiarEstado']
        )->name('citas.estado');

        /*
        |--------------------------------------------------------------------------
        | Pagos
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'pagos',
            PagoController::class
        )->only([
            'store',
            'update',
            'destroy',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Configuración - Estados de cita
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'estados-cita',
            EstadoCitaController::class
        )
        ->parameters([
            'estados-cita' => 'estado_cita',
        ])
        ->only([
            'store',
            'update',
            'destroy',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Configuración - Métodos de pago
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'metodos-pago',
            MetodoPagoController::class
        )
        ->parameters([
            'metodos-pago' => 'metodo_pago',
        ])
        ->only([
            'store',
            'update',
            'destroy',
        ]);
    });