<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profesionales\StoreProfesionalRequest;
use App\Http\Requests\Profesionales\UpdateProfesionalRequest;
use App\Models\Profesional;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfesionalController extends Controller
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

        $profesionales = Profesional::query()
            ->when($buscar, function ($query, $buscar) {
                $query->where(function ($query) use ($buscar) {
                    $query
                        ->where('nombres', 'ilike', "%{$buscar}%")
                        ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                        ->orWhere('numero_documento', 'ilike', "%{$buscar}%")
                        ->orWhere('numero_colegiatura', 'ilike', "%{$buscar}%")
                        ->orWhere('telefono', 'ilike', "%{$buscar}%")
                        ->orWhere('correo', 'ilike', "%{$buscar}%");
                });
            })
            ->when(
                $estado === 'activos',
                fn ($query) => $query->where('activo', true)
            )
            ->when(
                $estado === 'inactivos',
                fn ($query) => $query->where('activo', false)
            )
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render(
            'Profesionales/Index',
            [
                'profesionales' => $profesionales,

                'filtros' => [
                    'buscar' => $buscar,
                    'estado' => $estado,
                ],
            ]
        );
    }


    public function store(
        StoreProfesionalRequest $request
    ): RedirectResponse {

        Profesional::create(
            $request->validated()
        );

        return back()->with(
            'success',
            'Profesional registrado correctamente.'
        );
    }


    public function update(
        UpdateProfesionalRequest $request,
        Profesional $profesional
    ): RedirectResponse {

        $profesional->update(
            $request->validated()
        );

        return back()->with(
            'success',
            'Profesional actualizado correctamente.'
        );
    }


    public function desactivar(
        Profesional $profesional
    ): RedirectResponse {

        $profesional->update([
            'activo' => false,
        ]);

        return back()->with(
            'success',
            'Profesional desactivado correctamente.'
        );
    }


    public function reactivar(
        Profesional $profesional
    ): RedirectResponse {

        $profesional->update([
            'activo' => true,
        ]);

        return back()->with(
            'success',
            'Profesional reactivado correctamente.'
        );
    }
}