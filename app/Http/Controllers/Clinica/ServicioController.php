<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreServicioRequest;
use App\Http\Requests\Servicios\UpdateServicioRequest;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;

class ServicioController extends Controller
{
    public function store(
        StoreServicioRequest $request
    ): RedirectResponse {
        Servicio::create($request->validated());

        return back()->with(
            'success',
            'Servicio registrado correctamente.'
        );
    }

    public function update(
        UpdateServicioRequest $request,
        Servicio $servicio
    ): RedirectResponse {
        $servicio->update($request->validated());

        return back()->with(
            'success',
            'Servicio actualizado correctamente.'
        );
    }

    public function destroy(
        Servicio $servicio
    ): RedirectResponse {
        $servicio->update([
            'activo' => false,
        ]);

        return back()->with(
            'success',
            'Servicio desactivado correctamente.'
        );
    }
}