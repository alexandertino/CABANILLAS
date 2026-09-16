<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\TratamientoPaciente;
use App\Support\Clinica\AlcanceClinico;
use Illuminate\Http\JsonResponse;

class TratamientoController extends Controller
{
    public function activosPaciente(
        Paciente $paciente
    ): JsonResponse {

        AlcanceClinico::autorizarPaciente(
            request()->user(),
            $paciente
        );

        $tratamientos = AlcanceClinico::tratamientos(
            TratamientoPaciente::query(),
            request()->user()
        )
            ->with([
                'servicio:id,codigo,nombre,duracion_estimada_minutos,precio_actual',
                'profesional:id,nombres,apellidos',
                'tratamientoCitas' => function ($query) {
                    $query
                        ->with([
                            'cita.estadoCita:id,codigo,nombre',
                        ])
                        ->orderBy('numero_sesion');
                },
            ])
            ->where(
                'paciente_id',
                $paciente->id
            )
            ->whereIn(
                'estado',
                [
                    TratamientoPaciente::ESTADO_PLANIFICADO,
                    TratamientoPaciente::ESTADO_EN_PROCESO,
                ]
            )
            ->orderByDesc('id')
            ->get()
            ->map(
                function (TratamientoPaciente $tratamiento) {

                    $total =
                        $tratamiento->totalTratamiento();

                    $pagado =
                        $tratamiento->totalPagado();

                    $saldo = max(
                        $total - $pagado,
                        0
                    );

                    $ultimaSesion =
                        (int) (
                            $tratamiento
                                ->tratamientoCitas
                                ->max('numero_sesion')
                            ?? 0
                        );

                    return [
                        'id' =>
                            $tratamiento->id,

                        'paciente_id' =>
                            $tratamiento->paciente_id,

                        'servicio_id' =>
                            $tratamiento->servicio_id,

                        'profesional_id' =>
                            $tratamiento->profesional_id,

                        'precio_acordado' =>
                            (float) $tratamiento->precio_acordado,

                        'estado' =>
                            $tratamiento->estado,

                        'fecha_inicio' =>
                            $tratamiento
                                ->fecha_inicio
                                ?->format('Y-m-d'),

                        'observaciones' =>
                            $tratamiento->observaciones,

                        'servicio' =>
                            $tratamiento->servicio,

                        'profesional' =>
                            $tratamiento->profesional,

                        /*
                         * "numero_sesiones" se conserva por compatibilidad.
                         * Ahora además distinguimos las sesiones realmente
                         * atendidas de las ausencias y cancelaciones.
                         */
                        'numero_sesiones' =>
                            $tratamiento
                                ->tratamientoCitas
                                ->count(),

                        'sesiones_programadas' =>
                            $tratamiento
                                ->tratamientoCitas
                                ->count(),

                        'sesiones_realizadas' =>
                            $tratamiento
                                ->tratamientoCitas
                                ->filter(
                                    fn ($relacion) =>
                                        $relacion
                                            ->cita
                                            ?->estadoCita
                                            ?->codigo
                                        ===
                                        'ATENDIDA'
                                )
                                ->count(),

                        'sesiones_no_asistio' =>
                            $tratamiento
                                ->tratamientoCitas
                                ->filter(
                                    fn ($relacion) =>
                                        $relacion
                                            ->cita
                                            ?->estadoCita
                                            ?->codigo
                                        ===
                                        'NO_ASISTIO'
                                )
                                ->count(),

                        'sesiones_canceladas' =>
                            $tratamiento
                                ->tratamientoCitas
                                ->filter(
                                    fn ($relacion) =>
                                        $relacion
                                            ->cita
                                            ?->estadoCita
                                            ?->codigo
                                        ===
                                        'CANCELADA'
                                )
                                ->count(),

                        'sesiones_activas' =>
                            $tratamiento
                                ->tratamientoCitas
                                ->filter(
                                    fn ($relacion) =>
                                        in_array(
                                            $relacion
                                                ->cita
                                                ?->estadoCita
                                                ?->codigo,
                                            [
                                                'PENDIENTE',
                                                'CONFIRMADA',
                                                'EN_ATENCION',
                                            ],
                                            true
                                        )
                                )
                                ->count(),

                        'ultima_sesion' =>
                            $ultimaSesion,

                        'proxima_sesion' =>
                            $ultimaSesion + 1,

                        'total' =>
                            round($total, 2),

                        'pagado' =>
                            round($pagado, 2),

                        'saldo' =>
                            round($saldo, 2),

                        'estado_pago' =>
                            $tratamiento->estadoPago(),
                    ];
                }
            );

        return response()->json([
            'paciente' => [
                'id' => $paciente->id,
                'codigo' => $paciente->codigo,
                'nombres' => $paciente->nombres,
                'apellidos' => $paciente->apellidos,
            ],

            'tratamientos' =>
                $tratamientos,
        ]);
    }

    public function show(
        TratamientoPaciente $tratamiento
    ): JsonResponse {

        AlcanceClinico::autorizarTratamiento(
            request()->user(),
            $tratamiento
        );

        $tratamiento->load([
            'paciente',
            'servicio',
            'profesional',
            'citas' => function ($query) {
                $query
                    ->with([
                        'estadoCita',
                        'profesional',
                        'consultorio',
                    ])
                    ->orderBy('fecha_hora_inicio');
            },
        ]);

        $pagos = $tratamiento
            ->pagosContabilizables()
            ->with([
                'metodoPago',
                'usuarioRegistro',
                'usuarioAnulacion',
            ])
            ->orderByDesc('fecha_pago')
            ->get();

        return response()->json([
            'tratamiento' =>
                $tratamiento,

            'pagos' =>
                $pagos,

            'resumen_pago' => [
                'total' => round(
                    $tratamiento->totalTratamiento(),
                    2
                ),

                'pagado' => round(
                    $tratamiento->totalPagado(),
                    2
                ),

                'saldo' => round(
                    $tratamiento->saldoPendiente(),
                    2
                ),

                'estado' =>
                    $tratamiento->estadoPago(),
            ],

            'resumen_sesiones' =>
                $tratamiento
                    ->resumenSesiones(),
        ]);
    }
}
