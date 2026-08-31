<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstadosCita\StoreEstadoCitaRequest;
use App\Http\Requests\EstadosCita\UpdateEstadoCitaRequest;
use App\Models\EstadoCita;
use Illuminate\Http\RedirectResponse;

class EstadoCitaController extends Controller
{
    public function store(
        StoreEstadoCitaRequest $request
    ): RedirectResponse {
        EstadoCita::create($request->validated());

        return back()->with(
            'success',
            'Estado de cita registrado correctamente.'
        );
    }

    public function update(
        UpdateEstadoCitaRequest $request,
        EstadoCita $estadoCita
    ): RedirectResponse {
        $estadoCita->update($request->validated());

        return back()->with(
            'success',
            'Estado de cita actualizado correctamente.'
        );
    }

    public function destroy(
        EstadoCita $estadoCita
    ): RedirectResponse {
        $estadoCita->update([
            'activo' => false,
        ]);

        return back()->with(
            'success',
            'Estado de cita desactivado correctamente.'
        );
    }
}