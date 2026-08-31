<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pacientes\StorePacienteRequest;
use App\Http\Requests\Pacientes\UpdatePacienteRequest;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;

class PacienteController extends Controller
{
    public function store(
        StorePacienteRequest $request
    ): RedirectResponse {
        Paciente::create($request->validated());

        return back()->with(
            'success',
            'Paciente registrado correctamente.'
        );
    }

    public function update(
        UpdatePacienteRequest $request,
        Paciente $paciente
    ): RedirectResponse {
        $paciente->update($request->validated());

        return back()->with(
            'success',
            'Paciente actualizado correctamente.'
        );
    }

    public function destroy(
        Paciente $paciente
    ): RedirectResponse {
        /*
         * No eliminamos físicamente al paciente.
         *
         * Como puede tener citas y pagos asociados,
         * lo desactivamos.
         */
        $paciente->update([
            'activo' => false,
        ]);

        return back()->with(
            'success',
            'Paciente desactivado correctamente.'
        );
    }
}