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
use App\Http\Controllers\Clinica\TratamientoController;
use App\Http\Controllers\Clinica\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('clinica')
    ->middleware(['auth', 'activo'])
    ->name('clinica.')
    ->group(function () {

        Route::get(
            '/',
            [DashboardController::class, 'index']
        )->name('dashboard')
            ->middleware('can:dashboard.ver');

        /*
        |--------------------------------------------------------------------------
        | Pacientes
        |--------------------------------------------------------------------------
        */
        Route::get(
            'servicios/buscar',
            [ServicioController::class, 'buscar']
        )->name('servicios.buscar')
            ->middleware('can:servicios.ver');
        
        Route::get(
            'pacientes/buscar',
            [PacienteController::class, 'buscar']
        )->name('pacientes.buscar')
            ->middleware('can:pacientes.ver');

        Route::resource(
            'pacientes',
            PacienteController::class
        )->only([
            'index',
            'store',
            'update',
        ])
            ->middlewareFor('index', 'can:pacientes.ver')
            ->middlewareFor('store', 'can:pacientes.crear')
            ->middlewareFor('update', 'can:pacientes.editar');

        Route::patch(
            'pacientes/{paciente}/desactivar',
            [PacienteController::class, 'desactivar']
        )->name('pacientes.desactivar')
            ->middleware('can:pacientes.editar');

        Route::patch(
            'pacientes/{paciente}/reactivar',
            [PacienteController::class, 'reactivar']
        )->name('pacientes.reactivar')
            ->middleware('can:pacientes.editar');

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
        ])
            ->middlewareFor('index', 'can:profesionales.ver')
            ->middlewareFor('store', 'can:profesionales.crear')
            ->middlewareFor('update', 'can:profesionales.editar');

        Route::patch(
            'profesionales/{profesional}/desactivar',
            [ProfesionalController::class, 'desactivar']
        )->name('profesionales.desactivar')
            ->middleware('can:profesionales.editar');

        Route::patch(
            'profesionales/{profesional}/reactivar',
            [ProfesionalController::class, 'reactivar']
        )->name('profesionales.reactivar')
            ->middleware('can:profesionales.editar');

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
        ])
            ->middlewareFor('index', 'can:consultorios.ver')
            ->middlewareFor('store', 'can:consultorios.crear')
            ->middlewareFor('update', 'can:consultorios.editar');

        Route::patch(
            'consultorios/{consultorio}/desactivar',
            [ConsultorioController::class, 'desactivar']
        )->name('consultorios.desactivar')
            ->middleware('can:consultorios.editar');

        Route::patch(
            'consultorios/{consultorio}/reactivar',
            [ConsultorioController::class, 'reactivar']
        )->name('consultorios.reactivar')
            ->middleware('can:consultorios.editar');

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
        ])
            ->middlewareFor('index', 'can:servicios.ver')
            ->middlewareFor('store', 'can:servicios.crear')
            ->middlewareFor('update', 'can:servicios.editar');

        Route::patch(
            'servicios/{servicio}/desactivar',
            [ServicioController::class, 'desactivar']
        )->name('servicios.desactivar')
            ->middleware('can:servicios.editar');

        Route::patch(
            'servicios/{servicio}/reactivar',
            [ServicioController::class, 'reactivar']
        )->name('servicios.reactivar')
            ->middleware('can:servicios.editar');

        /*
        |--------------------------------------------------------------------------
        | Citas
        |--------------------------------------------------------------------------
        */
        Route::get(
            'citas/agenda',
            [CitaController::class, 'agenda']
        )->name('citas.agenda')
            ->middleware('can:citas.ver');

        Route::get(
            'citas/agenda-semana',
            [CitaController::class, 'agendaSemana']
        )->name('citas.agenda-semana')
            ->middleware('can:citas.ver');

        Route::get(
            'citas/disponibilidad',
            [CitaController::class, 'disponibilidad']
        )->name('citas.disponibilidad')
            ->middleware('can:citas.ver');

        Route::resource(
            'citas',
            CitaController::class
        )->only([
            'index',
            'store',
            'update',
        ])
            ->middlewareFor('index', 'can:citas.ver')
            ->middlewareFor('store', 'can:citas.crear')
            ->middlewareFor('update', 'can:citas.editar');

        Route::patch(
            'citas/{cita}/finalizar-atencion',
            [
                CitaController::class,
                'finalizarAtencion',
            ]
        )->name(
            'citas.finalizar-atencion'
        )->middleware('can:citas.finalizar');
        
        Route::patch(
            'citas/{cita}/cancelar',
            [CitaController::class, 'cancelar']
        )->name('citas.cancelar')
            ->middleware('can:citas.cancelar');

        Route::patch(
            'citas/{cita}/estado',
            [CitaController::class, 'cambiarEstado']
        )->name('citas.estado');
            
        Route::post(
            'citas/{cita}/reprogramar',
            [CitaController::class, 'reprogramar']
        )->name('citas.reprogramar')
            ->middleware('can:citas.reprogramar');

        /*
        |--------------------------------------------------------------------------
        | Pagos
        |--------------------------------------------------------------------------
        */

        Route::get(
            'pagos',
            [PagoController::class, 'index']
        )->name(
            'pagos.index'
        )->middleware('can:pagos.ver');

        Route::get(
            'pagos/buscar-citas',
            [PagoController::class, 'buscarCitas']
        )->name(
            'pagos.buscar-citas'
        )->middleware('can:pagos.ver');


        Route::get(
            'pagos/pendientes',
            [PagoController::class, 'pendientes']
        )->name(
            'pagos.pendientes'
        )->middleware('can:pagos.ver');

        Route::post(
            'pagos',
            [PagoController::class, 'store']
        )->name(
            'pagos.store'
        )->middleware('can:pagos.registrar');

        Route::patch(
            'pagos/{pago}/anular',
            [PagoController::class, 'anular']
        )->name(
            'pagos.anular'
        )->middleware('can:pagos.anular');

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
        ])
            ->middleware('role:ADMINISTRADOR');

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
        ])
            ->middleware('role:ADMINISTRADOR');

        Route::get(
            'pacientes/{paciente}/tratamientos-activos',
            [
                TratamientoController::class,
                'activosPaciente',
            ]
        )->name(
            'pacientes.tratamientos-activos'
        )->middleware('can:tratamientos.ver');


        Route::get(
            'tratamientos/{tratamiento}',
            [
                TratamientoController::class,
                'show',
            ]
        )->name(
            'tratamientos.show'
        )->middleware('can:tratamientos.ver');

        /*
        |--------------------------------------------------------------------------
        | Usuarios
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'usuarios',
            UsuarioController::class
        )->only([
            'index',
            'store',
            'update',
        ])
            ->middlewareFor('index', 'can:usuarios.ver')
            ->middlewareFor('store', 'can:usuarios.crear')
            ->middlewareFor('update', 'can:usuarios.editar');

        Route::patch(
            'usuarios/{usuario}/desactivar',
            [UsuarioController::class, 'desactivar']
        )->name('usuarios.desactivar')
            ->middleware('can:usuarios.editar');

        Route::patch(
            'usuarios/{usuario}/reactivar',
            [UsuarioController::class, 'reactivar']
        )->name('usuarios.reactivar')
            ->middleware('can:usuarios.editar');
});