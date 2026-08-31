<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\StoreCitaRequest;
use App\Http\Requests\Citas\UpdateCitaRequest;
use App\Models\Cita;
use App\Models\HistorialEstadoCita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CitaController extends Controller
{
    public function store(
        StoreCitaRequest $request
    ): RedirectResponse {
        DB::transaction(function () use ($request) {

            $datos = $request->validated();

            $datos['usuario_creador_id'] = Auth::id();

            $cita = Cita::create($datos);

            HistorialEstadoCita::create([
                'cita_id' => $cita->id,

                'estado_anterior_id' => null,

                'estado_nuevo_id' =>
                    $cita->estado_cita_id,

                'usuario_id' => Auth::id(),

                'motivo' =>
                    'Creación inicial de la cita',

                'fecha_cambio' => now(),
            ]);
        });

        return back()->with(
            'success',
            'Cita registrada correctamente.'
        );
    }

    public function update(
        UpdateCitaRequest $request,
        Cita $cita
    ): RedirectResponse {
        DB::transaction(function () use (
            $request,
            $cita
        ) {

            $estadoAnterior =
                $cita->estado_cita_id;

            $datos =
                $request->validated();

            $cita->update($datos);

            /*
             * Registramos historial únicamente
             * si cambió el estado.
             */
            if (
                $estadoAnterior !==
                $cita->estado_cita_id
            ) {
                HistorialEstadoCita::create([
                    'cita_id' =>
                        $cita->id,

                    'estado_anterior_id' =>
                        $estadoAnterior,

                    'estado_nuevo_id' =>
                        $cita->estado_cita_id,

                    'usuario_id' =>
                        Auth::id(),

                    'motivo' =>
                        'Cambio de estado de la cita',

                    'fecha_cambio' =>
                        now(),
                ]);
            }
        });

        return back()->with(
            'success',
            'Cita actualizada correctamente.'
        );
    }

    public function destroy(
        Cita $cita
    ): RedirectResponse {
        /*
         * Por ahora no eliminaremos citas
         * físicamente.
         *
         * Más adelante crearemos una operación
         * específica "cancelar cita".
         */

        return back()->with(
            'warning',
            'Las citas no se eliminan. Deben cancelarse.'
        );
    }
}