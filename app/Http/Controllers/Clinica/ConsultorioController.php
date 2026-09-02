<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultorios\StoreConsultorioRequest;
use App\Http\Requests\Consultorios\UpdateConsultorioRequest;
use App\Models\Consultorio;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsultorioController extends Controller
{
    public function index(): Response
    {
        $buscar = request()
            ->string('buscar')
            ->trim()
            ->toString();

        $estado = request()
            ->string('estado', 'todos')
            ->toString();

        $consultorios = Consultorio::query()

            ->when($buscar, function ($query, $buscar) {
                $query->where(function ($query) use ($buscar) {
                    $query
                        ->where('codigo', 'ilike', "%{$buscar}%")
                        ->orWhere('nombre', 'ilike', "%{$buscar}%")
                        ->orWhere('descripcion', 'ilike', "%{$buscar}%");
                });
            })

            ->when(
                $estado === 'activos',
                fn ($query) =>
                    $query->where('activo', true)
            )

            ->when(
                $estado === 'inactivos',
                fn ($query) =>
                    $query->where('activo', false)
            )

            ->orderBy('nombre')

            ->paginate(10)

            ->withQueryString();

        return Inertia::render(
            'Consultorios/Index',
            [
                'consultorios' => $consultorios,

                'filtros' => [
                    'buscar' => $buscar,
                    'estado' => $estado,
                ],
            ]
        );
    }


    public function store(
        StoreConsultorioRequest $request
    ): RedirectResponse {

        Consultorio::create(
            $request->validated()
        );

        return back()->with(
            'success',
            'Consultorio registrado correctamente.'
        );
    }


    public function update(
        UpdateConsultorioRequest $request,
        Consultorio $consultorio
    ): RedirectResponse {

        $consultorio->update(
            $request->validated()
        );

        return back()->with(
            'success',
            'Consultorio actualizado correctamente.'
        );
    }


    public function desactivar(
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


    public function reactivar(
        Consultorio $consultorio
    ): RedirectResponse {

        $consultorio->update([
            'activo' => true,
        ]);

        return back()->with(
            'success',
            'Consultorio reactivado correctamente.'
        );
    }
}