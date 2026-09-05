<?php

use App\Http\Controllers\Api\AgendaController;
use Illuminate\Support\Facades\Route;

Route::middleware('clinic.api')->group(function () {
    Route::get('pacientes', [AgendaController::class, 'patient']);
    Route::post('pacientes', [AgendaController::class, 'createPatient']);
    Route::get('servicios', [AgendaController::class, 'services']);
    Route::get('profesionales', [AgendaController::class, 'professionals']);
    Route::get('disponibilidad', [AgendaController::class, 'availability']);
    Route::post('citas', [AgendaController::class, 'createAppointment']);
    Route::get('citas/{cita}', [AgendaController::class, 'appointment']);
    Route::post('citas/{cita}/confirmar', [AgendaController::class, 'confirmAppointment']);
    Route::post('citas/{cita}/cancelar', [AgendaController::class, 'cancelAppointment']);
    Route::post('citas/{cita}/reprogramar', [AgendaController::class, 'rescheduleAppointment']);
});
