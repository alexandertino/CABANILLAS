<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pacientes\StorePacienteRequest;
use App\Http\Requests\Pacientes\UpdatePacienteRequest;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    public function index(): Response
    {
        $buscar = request()->string('buscar')->trim()->toString();
        $estado = request()->string('estado', 'todos')->toString();

        $pacientes = Paciente::query()

            ->when($buscar, function ($query, $buscar) {
                $query->where(function ($query) use ($buscar) {

                    $query
                        ->where('codigo', 'ilike', "%{$buscar}%")
                        ->orWhere('numero_documento', 'ilike', "%{$buscar}%")
                        ->orWhere('nombres', 'ilike', "%{$buscar}%")
                        ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                        ->orWhere('telefono', 'ilike', "%{$buscar}%");

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

        return Inertia::render('Pacientes/Index', [
            'pacientes' => $pacientes,

            'filtros' => [
                'buscar' => $buscar,
                'estado' => $estado,
            ],
        ]);
    }

    public function store(
        StorePacienteRequest $request
    ): RedirectResponse {

        DB::transaction(function () use ($request) {

            $datos = $request->validated();

            /*
            |--------------------------------------------------------------------------
            | Código temporal
            |--------------------------------------------------------------------------
            |
            | La columna codigo es única y posiblemente no acepta null.
            | Primero insertamos un código temporal único.
            |
            */

            $datos['codigo'] =
                'TMP-' . Str::ulid();

            $paciente = Paciente::create($datos);


            /*
            |--------------------------------------------------------------------------
            | Código definitivo
            |--------------------------------------------------------------------------
            |
            | Ejemplos:
            |
            | ID 1   -> PAC-000001
            | ID 25  -> PAC-000025
            | ID 950 -> PAC-000950
            |
            */

            $paciente->update([
                'codigo' =>
                    'PAC-' .
                    str_pad(
                        (string) $paciente->id,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),
            ]);

        });

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
    
    public function desactivar(
        Paciente $paciente
    ): RedirectResponse {

        $paciente->update([
            'activo' => false,
        ]);

        return back()->with(
            'success',
            'Paciente desactivado correctamente.'
        );
    }


    public function reactivar(
        Paciente $paciente
    ): RedirectResponse {

        $paciente->update([
            'activo' => true,
        ]);

        return back()->with(
            'success',
            'Paciente reactivado correctamente.'
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


        $pacientes = Paciente::query()

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
                            'numero_documento',
                            'ilike',
                            "%{$buscar}%"
                        )

                        ->orWhere(
                            'nombres',
                            'ilike',
                            "%{$buscar}%"
                        )

                        ->orWhere(
                            'apellidos',
                            'ilike',
                            "%{$buscar}%"
                        );
                }
            )

            ->select([
                'id',
                'codigo',
                'tipo_documento',
                'numero_documento',
                'nombres',
                'apellidos',
                'telefono',
            ])

            ->orderBy('apellidos')
            ->orderBy('nombres')

            ->limit(8)

            ->get();


        return response()->json(
            $pacientes
        );
    }

}