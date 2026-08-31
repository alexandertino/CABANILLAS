<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultorios\StoreConsultorioRequest;
use App\Http\Requests\Consultorios\UpdateConsultorioRequest;
use App\Models\Consultorio;
use Illuminate\Http\RedirectResponse;

class ConsultorioController extends Controller
{
    public function store(
        StoreConsultorioRequest $request
    ): RedirectResponse {
        Consultorio::create($request->validated());

        return back()->with(
            'success',
            'Consultorio registrado correctamente.'
        );
    }

    public function update(
        UpdateConsultorioRequest $request,
        Consultorio $consultorio
    ): RedirectResponse {
        $consultorio->update($request->validated());

        return back()->with(
            'success',
            'Consultorio actualizado correctamente.'
        );
    }

    public function destroy(
        Consultorio $consultorio
    ): RedirectResponse {
        $consultorio->update([
            'activo' => false,
        ]);

        return back()->with(
            'success',
            'Consultorio desactivado correctamente.'
        );
    }
}