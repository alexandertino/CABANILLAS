<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pagos\StorePagoRequest;
use App\Http\Requests\Pagos\UpdatePagoRequest;
use App\Models\Pago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PagoController extends Controller
{
    public function store(
        StorePagoRequest $request
    ): RedirectResponse {
        $datos = $request->validated();

        $datos['usuario_recibio_id'] =
            Auth::id();

        Pago::create($datos);

        return back()->with(
            'success',
            'Pago registrado correctamente.'
        );
    }

    public function update(
        UpdatePagoRequest $request,
        Pago $pago
    ): RedirectResponse {
        $pago->update(
            $request->validated()
        );

        return back()->with(
            'success',
            'Pago actualizado correctamente.'
        );
    }

    public function destroy(
        Pago $pago
    ): RedirectResponse {

        /*
         * Un pago tampoco debería borrarse
         * físicamente.
         */

        $pago->update([
            'estado' => 'anulado',
        ]);

        return back()->with(
            'success',
            'Pago anulado correctamente.'
        );
    }
}