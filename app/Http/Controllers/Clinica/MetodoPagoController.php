<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\MetodosPago\StoreMetodoPagoRequest;
use App\Http\Requests\MetodosPago\UpdateMetodoPagoRequest;
use App\Models\MetodoPago;
use Illuminate\Http\RedirectResponse;

class MetodoPagoController extends Controller
{
    public function store(
        StoreMetodoPagoRequest $request
    ): RedirectResponse {
        MetodoPago::create($request->validated());

        return back()->with(
            'success',
            'Método de pago registrado correctamente.'
        );
    }

    public function update(
        UpdateMetodoPagoRequest $request,
        MetodoPago $metodoPago
    ): RedirectResponse {
        $metodoPago->update($request->validated());

        return back()->with(
            'success',
            'Método de pago actualizado correctamente.'
        );
    }

    public function destroy(
        MetodoPago $metodoPago
    ): RedirectResponse {
        $metodoPago->update([
            'activo' => false,
        ]);

        return back()->with(
            'success',
            'Método de pago desactivado correctamente.'
        );
    }
}