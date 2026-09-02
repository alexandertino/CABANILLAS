<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreServicioRequest;
use App\Http\Requests\Servicios\UpdateServicioRequest;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicioController extends Controller
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

        $servicios = Servicio::query()

            ->when($buscar, function ($query, $buscar) {

                $query->where(function ($query) use ($buscar) {

                    $query
                        ->where(
                            'codigo',
                            'ilike',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'nombre',
                            'ilike',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'descripcion',
                            'ilike',
                            "%{$buscar}%"
                        );

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

            ->paginate(12)

            ->withQueryString();

        return Inertia::render(
            'Servicios/Index',
            [
                'servicios' => $servicios,

                'filtros' => [
                    'buscar' => $buscar,
                    'estado' => $estado,
                ],
            ]
        );
    }


    public function store(
        StoreServicioRequest $request
    ): RedirectResponse {

        Servicio::create(
            $request->validated()
        );

        return back()->with(
            'success',
            'Servicio registrado correctamente.'
        );
    }


    public function update(
        UpdateServicioRequest $request,
        Servicio $servicio
    ): RedirectResponse {

        $servicio->update(
            $request->validated()
        );

        return back()->with(
            'success',
            'Servicio actualizado correctamente.'
        );
    }


    public function desactivar(
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


    public function reactivar(
        Servicio $servicio
    ): RedirectResponse {

        $servicio->update([
            'activo' => true,
        ]);

        return back()->with(
            'success',
            'Servicio reactivado correctamente.'
        );
    }

    public function buscar(
        Request $request
    ): JsonResponse {

        $buscar = trim(
            (string) $request->query(
                'q',
                ''
            )
        );


        if (
            mb_strlen($buscar) < 2
        ) {
            return response()->json([]);
        }


        $servicios = Servicio::query()

            ->where(
                'activo',
                true
            )

            ->where(
                function ($query) use ($buscar) {

                    $query

                        ->where(
                            'codigo',
                            'ilike',
                            "%{$buscar}%"
                        )

                        ->orWhere(
                            'nombre',
                            'ilike',
                            "%{$buscar}%"
                        )

                        ->orWhere(
                            'descripcion',
                            'ilike',
                            "%{$buscar}%"
                        );
                }
            )

            ->select([
                'id',
                'codigo',
                'nombre',
                'duracion_estimada_minutos',
                'precio_actual',
            ])

            ->orderBy('nombre')

            ->limit(8)

            ->get();


        return response()->json(
            $servicios
        );
    }
}