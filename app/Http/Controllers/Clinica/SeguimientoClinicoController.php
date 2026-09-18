<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinica\GuardarSeguimientoClinicoRequest;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\SeguimientoClinico;
use App\Models\TratamientoCita;
use App\Models\TratamientoPaciente;
use App\Models\User;
use App\Support\Auditoria;
use App\Support\Clinica\AlcanceClinico;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class SeguimientoClinicoController extends Controller
{
    public function pagina(
        Paciente $paciente
    ): Response {
        /** @var User $usuario */
        $usuario = request()->user();

        AlcanceClinico::autorizarPaciente(
            $usuario,
            $paciente
        );

        return Inertia::render(
            'Seguimientos/Index',
            [
                'paciente' => [
                    'id' =>
                        $paciente->id,

                    'codigo' =>
                        $paciente->codigo,

                    'nombres' =>
                        $paciente->nombres,

                    'apellidos' =>
                        $paciente->apellidos,
                ],
            ]
        );
    }

    public function index(
        Paciente $paciente
    ): JsonResponse {
        /** @var User $usuario */
        $usuario = request()->user();

        AlcanceClinico::autorizarPaciente(
            $usuario,
            $paciente
        );

        $seguimientos = AlcanceClinico::seguimientos(
            SeguimientoClinico::query()
                ->where(
                    'paciente_id',
                    $paciente->id
                ),
            $usuario
        )
            ->with([
                'tratamiento.servicio',
                'cita.estadoCita',
                'profesional',
                'imagenes',
            ])
            ->orderByDesc(
                'fecha_seguimiento'
            )
            ->orderByDesc('id')
            ->paginate(20);

        $tratamientos = AlcanceClinico::tratamientos(
            TratamientoPaciente::query()
                ->where(
                    'paciente_id',
                    $paciente->id
                ),
            $usuario
        )
            ->with('servicio')
            ->orderByDesc('id')
            ->get()
            ->map(fn (
                TratamientoPaciente $tratamiento
            ) => [
                'id' => $tratamiento->id,

                'estado' =>
                    $tratamiento->estado,

                'servicio' =>
                    $tratamiento
                        ->servicio
                        ?->nombre,

                'precio_acordado' =>
                    $tratamiento
                        ->precio_acordado,
            ])
            ->values();

        $citas = AlcanceClinico::citas(
            Cita::query()
                ->where(
                    'paciente_id',
                    $paciente->id
                ),
            $usuario
        )
            ->with('estadoCita')
            ->orderByDesc(
                'fecha_hora_inicio'
            )
            ->limit(50)
            ->get()
            ->map(fn (Cita $cita) => [
                'id' =>
                    $cita->id,

                'fecha_hora_inicio' =>
                    optional(
                        $cita
                            ->fecha_hora_inicio
                    )->format(
                        'Y-m-d H:i:s'
                    ),

                'estado' =>
                    $cita
                        ->estadoCita
                        ?->codigo,

                'estado_nombre' =>
                    $cita
                        ->estadoCita
                        ?->nombre,
            ])
            ->values();

        return response()->json([
            'seguimientos' =>
                $seguimientos,

            'opciones' => [
                'tratamientos' =>
                    $tratamientos,

                'citas' =>
                    $citas,
            ],
        ]);
    }

    public function store(
        GuardarSeguimientoClinicoRequest $request,
        Paciente $paciente
    ): JsonResponse {
        /** @var User $usuario */
        $usuario = $request->user();

        AlcanceClinico::autorizarPaciente(
            $usuario,
            $paciente
        );

        $profesionalId =
            AlcanceClinico::profesionalId(
                $usuario
            );

        $datos = $request->validated();

        $this->validarVinculos(
            $usuario,
            $paciente,
            $datos
        );

        $seguimiento = DB::transaction(
            function () use (
                $datos,
                $paciente,
                $usuario,
                $profesionalId
            ): SeguimientoClinico {
                $seguimiento =
                    SeguimientoClinico::create([
                        'paciente_id' =>
                            $paciente->id,

                        'tratamiento_paciente_id' =>
                            $datos[
                                'tratamiento_paciente_id'
                            ] ?? null,

                        'cita_id' =>
                            $datos['cita_id']
                            ?? null,

                        'profesional_id' =>
                            $profesionalId,

                        'usuario_creador_id' =>
                            $usuario->id,

                        'fecha_seguimiento' =>
                            $datos[
                                'fecha_seguimiento'
                            ],

                        'titulo' =>
                            $datos['titulo'],

                        'observaciones' =>
                            $datos[
                                'observaciones'
                            ] ?? null,
                    ]);

                Auditoria::registrar(
                    modulo: 'seguimientos',
                    accion: 'crear',
                    descripcion:
                        'Creó un seguimiento clínico.',
                    sujeto: $seguimiento,
                    antes: [],
                    despues:
                        Auditoria::atributos(
                            $seguimiento,
                            Auditoria::SEGUIMIENTO
                        ),
                    propiedades: [
                        'tiene_observaciones' =>
                            filled(
                                $seguimiento
                                    ->observaciones
                            ),
                    ],
                    causante: $usuario
                );

                return $seguimiento;
            }
        );

        $seguimiento->load([
            'tratamiento',
            'cita.estadoCita',
            'profesional',
            'imagenes',
        ]);

        return response()->json([
            'message' =>
                'Seguimiento clínico registrado correctamente.',

            'seguimiento' =>
                $seguimiento,
        ], 201);
    }

    public function update(
        GuardarSeguimientoClinicoRequest $request,
        SeguimientoClinico $seguimiento
    ): JsonResponse {
        /** @var User $usuario */
        $usuario = $request->user();

        AlcanceClinico::autorizarSeguimiento(
            $usuario,
            $seguimiento
        );

        $paciente =
            Paciente::query()
                ->findOrFail(
                    $seguimiento->paciente_id
                );

        AlcanceClinico::autorizarPaciente(
            $usuario,
            $paciente
        );

        $datos = $request->validated();

        $this->validarVinculos(
            $usuario,
            $paciente,
            $datos
        );

        $antes =
            Auditoria::atributos(
                $seguimiento,
                Auditoria::SEGUIMIENTO
            );

        $observacionesAntes =
            $seguimiento->observaciones;

        DB::transaction(
            function () use (
                $seguimiento,
                $datos
            ): void {
                $seguimiento->update([
                    'tratamiento_paciente_id' =>
                        $datos[
                            'tratamiento_paciente_id'
                        ] ?? null,

                    'cita_id' =>
                        $datos['cita_id']
                        ?? null,

                    'fecha_seguimiento' =>
                        $datos[
                            'fecha_seguimiento'
                        ],

                    'titulo' =>
                        $datos['titulo'],

                    'observaciones' =>
                        $datos[
                            'observaciones'
                        ] ?? null,
                ]);
            }
        );

        $seguimiento->refresh();

        $despues =
            Auditoria::atributos(
                $seguimiento,
                Auditoria::SEGUIMIENTO
            );

        Auditoria::registrar(
            modulo: 'seguimientos',
            accion: 'editar',
            descripcion:
                'Actualizó un seguimiento clínico.',
            sujeto: $seguimiento,
            antes: $antes,
            despues: $despues,
            propiedades: [
                'observaciones_actualizadas' =>
                    $observacionesAntes
                    !== $seguimiento
                        ->observaciones,
            ],
            causante: $usuario
        );

        $seguimiento->load([
            'tratamiento',
            'cita.estadoCita',
            'profesional',
            'imagenes',
        ]);

        return response()->json([
            'message' =>
                'Seguimiento clínico actualizado correctamente.',

            'seguimiento' =>
                $seguimiento,
        ]);
    }

    private function validarVinculos(
        User $usuario,
        Paciente $paciente,
        array $datos
    ): void {
        $tratamiento = null;
        $cita = null;

        if (
            !empty(
                $datos[
                    'tratamiento_paciente_id'
                ]
            )
        ) {
            $tratamiento =
                TratamientoPaciente::query()
                    ->findOrFail(
                        $datos[
                            'tratamiento_paciente_id'
                        ]
                    );

            if (
                (int) $tratamiento->paciente_id
                !==
                (int) $paciente->id
            ) {
                throw ValidationException::withMessages([
                    'tratamiento_paciente_id' =>
                        'El tratamiento no pertenece al paciente seleccionado.',
                ]);
            }

            AlcanceClinico::autorizarTratamiento(
                $usuario,
                $tratamiento
            );
        }

        if (
            !empty(
                $datos['cita_id']
            )
        ) {
            $cita =
                Cita::query()
                    ->findOrFail(
                        $datos['cita_id']
                    );

            if (
                (int) $cita->paciente_id
                !==
                (int) $paciente->id
            ) {
                throw ValidationException::withMessages([
                    'cita_id' =>
                        'La cita no pertenece al paciente seleccionado.',
                ]);
            }

            AlcanceClinico::autorizarCita(
                $usuario,
                $cita
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Si se envían tratamiento Y cita
        |--------------------------------------------------------------------------
        |
        | Confirmamos que realmente estén vinculados entre sí.
        |
        */

        if (
            $tratamiento !== null
            &&
            $cita !== null
        ) {
            $vinculados =
                TratamientoCita::query()
                    ->where(
                        'tratamiento_paciente_id',
                        $tratamiento->id
                    )
                    ->where(
                        'cita_id',
                        $cita->id
                    )
                    ->exists();

            if (!$vinculados) {
                throw ValidationException::withMessages([
                    'cita_id' =>
                        'La cita seleccionada no pertenece al tratamiento indicado.',
                ]);
            }
        }
    }
}