<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pagos\AnularPagoRequest;
use App\Http\Requests\Pagos\GuardarPagoRequest;
use App\Models\Cita;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Paciente;
use App\Models\TratamientoPaciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PagoController extends Controller
{
    public function index(Request $request): Response
    {
        $buscar = $request->string('buscar')->trim()->toString();
        $estado = $request->string('estado')->toString();
        $metodoPagoId = $request->integer('metodo_pago_id');

        $pagos = Pago::query()
            ->with([
                'paciente:id,codigo,nombres,apellidos,numero_documento',
                'metodoPago:id,codigo,nombre',

                'cita' => function ($query) {
                    $query->select([
                        'id',
                        'paciente_id',
                        'fecha_hora_inicio',
                        'motivo',
                    ])->with([
                        'serviciosCita.servicio:id,codigo,nombre',
                    ]);
                },

                'tratamientoPaciente' => function ($query) {
                    $query->select([
                        'id',
                        'paciente_id',
                        'servicio_id',
                        'precio_acordado',
                        'estado',
                    ])->with([
                        'servicio:id,codigo,nombre',
                    ]);
                },

                'usuarioRegistro:id,name',
                'usuarioAnulacion:id,name',
            ])
            ->when(
                $buscar,
                function ($query, $buscar) {
                    $query->whereHas(
                        'paciente',
                        function ($query) use ($buscar) {
                            $query->where(
                                function ($query) use ($buscar) {
                                    $query
                                        ->where(
                                            'nombres',
                                            'ilike',
                                            "%{$buscar}%"
                                        )
                                        ->orWhere(
                                            'apellidos',
                                            'ilike',
                                            "%{$buscar}%"
                                        )
                                        ->orWhere(
                                            'numero_documento',
                                            'ilike',
                                            "%{$buscar}%"
                                        )
                                        ->orWhere(
                                            'codigo',
                                            'ilike',
                                            "%{$buscar}%"
                                        );
                                }
                            );
                        }
                    );
                }
            )
            ->when(
                in_array(
                    $estado,
                    [
                        Pago::ESTADO_REGISTRADO,
                        Pago::ESTADO_ANULADO,
                    ],
                    true
                ),
                fn ($query) =>
                    $query->where(
                        'estado',
                        $estado
                    )
            )
            ->when(
                $metodoPagoId,
                fn ($query) =>
                    $query->where(
                        'metodo_pago_id',
                        $metodoPagoId
                    )
            )
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $pagos->getCollection()->transform(
            function (Pago $pago) {
                $pago->setAttribute(
                    'tipo_origen',
                    $pago->tratamiento_paciente_id
                        ? 'TRATAMIENTO'
                        : 'CITA_SIMPLE'
                );

                $pago->setAttribute(
                    'concepto',
                    $this->conceptoPago($pago)
                );

                return $pago;
            }
        );

        $metodosPago = MetodoPago::query()
            ->where('activo', true)
            ->select([
                'id',
                'codigo',
                'nombre',
            ])
            ->orderBy('nombre')
            ->get();

        $resumen = [
            'registrado_hoy' =>
                (float)
                Pago::query()
                    ->where(
                        'estado',
                        Pago::ESTADO_REGISTRADO
                    )
                    ->whereDate(
                        'fecha_pago',
                        now()->toDateString()
                    )
                    ->sum('monto'),

            'pagos_hoy' =>
                Pago::query()
                    ->where(
                        'estado',
                        Pago::ESTADO_REGISTRADO
                    )
                    ->whereDate(
                        'fecha_pago',
                        now()->toDateString()
                    )
                    ->count(),

            'registrados' =>
                Pago::query()
                    ->where(
                        'estado',
                        Pago::ESTADO_REGISTRADO
                    )
                    ->count(),

            'anulados' =>
                Pago::query()
                    ->where(
                        'estado',
                        Pago::ESTADO_ANULADO
                    )
                    ->count(),
        ];

        return Inertia::render(
            'Pagos/Index',
            [
                'pagos' => $pagos,
                'metodosPago' => $metodosPago,
                'resumen' => $resumen,

                'filtros' => [
                    'buscar' => $buscar,
                    'estado' => $estado,
                    'metodo_pago_id' => $metodoPagoId,
                ],
            ]
        );
    }

    /**
     * La ruta ya se llamaba buscar-citas.
     * Se conserva, pero ahora devuelve deudas pagables:
     * tratamientos y citas simples.
     */
    public function buscarCitas(
        Request $request
    ): JsonResponse {

        $datos = $request->validate([
            'paciente_id' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],
        ]);

        $paciente = Paciente::query()
            ->select([
                'id',
                'codigo',
                'nombres',
                'apellidos',
                'numero_documento',
            ])
            ->findOrFail(
                $datos['paciente_id']
            );

        /*
        |--------------------------------------------------------------------------
        | TRATAMIENTOS CON SALDO
        |--------------------------------------------------------------------------
        */

        $tratamientos = TratamientoPaciente::query()
            ->where(
                'paciente_id',
                $paciente->id
            )
            ->where(
                'estado',
                '!=',
                TratamientoPaciente::ESTADO_CANCELADO
            )
            ->with([
                'servicio:id,codigo,nombre',
                'profesional:id,nombres,apellidos',
                'tratamientoCitas.cita.estadoCita:id,codigo,nombre',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(
                function (
                    TratamientoPaciente $tratamiento
                ) {
                    $total = $tratamiento->totalTratamiento();
                    $pagado = $tratamiento->totalPagado();
                    $saldo = $tratamiento->saldoPendiente();

                    if ($saldo <= 0) {
                        return null;
                    }

                    $sesiones = $tratamiento
                        ->tratamientoCitas
                        ->sortBy('numero_sesion')
                        ->values()
                        ->map(
                            function ($relacion) {
                                return [
                                    'cita_id' =>
                                        $relacion->cita_id,

                                    'numero_sesion' =>
                                        $relacion->numero_sesion,

                                    'fecha_hora_inicio' =>
                                        $relacion
                                            ->cita
                                            ?->fecha_hora_inicio,

                                    'estado' =>
                                        $relacion
                                            ->cita
                                            ?->estadoCita
                                            ?->codigo,
                                ];
                            }
                        );

                    return [
                        'tipo' =>
                            'TRATAMIENTO',

                        'id' =>
                            'tratamiento-'
                            .
                            $tratamiento->id,

                        'tratamiento_paciente_id' =>
                            $tratamiento->id,

                        'cita_id' =>
                            null,

                        'concepto' =>
                            $tratamiento
                                ->servicio
                                ?->nombre
                            ??
                            'Tratamiento odontológico',

                        'detalle' =>
                            'Tratamiento · '
                            .
                            $tratamiento->estado,

                        'estado_clinico' =>
                            $tratamiento->estado,

                        'total' =>
                            round(
                                $total,
                                2
                            ),

                        'pagado' =>
                            round(
                                $pagado,
                                2
                            ),

                        'saldo' =>
                            round(
                                $saldo,
                                2
                            ),

                        'estado_pago' =>
                            $tratamiento->estadoPago(),

                        'sesiones' =>
                            $sesiones,
                    ];
                }
            )
            ->filter()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | CITAS SIMPLES CON SALDO
        |--------------------------------------------------------------------------
        */

        $citas = Cita::query()
            ->where(
                'paciente_id',
                $paciente->id
            )
            ->whereDoesntHave(
                'tratamientoCita'
            )
            ->whereHas(
                'estadoCita',
                function ($query) {
                    $query->whereNotIn(
                        'codigo',
                        [
                            'CANCELADA',
                            'REPROGRAMADA',
                            'NO_ASISTIO',
                        ]
                    );
                }
            )
            ->with([
                'estadoCita:id,codigo,nombre',
                'serviciosCita.servicio:id,codigo,nombre',
            ])
            ->orderByDesc(
                'fecha_hora_inicio'
            )
            ->get()
            ->map(
                function (Cita $cita) {
                    $total =
                        (float)
                        $cita
                            ->serviciosCita
                            ->sum('total');

                    $pagado =
                        (float)
                        Pago::query()
                            ->where(
                                'cita_id',
                                $cita->id
                            )
                            ->whereNull(
                                'tratamiento_paciente_id'
                            )
                            ->where(
                                'estado',
                                Pago::ESTADO_REGISTRADO
                            )
                            ->sum('monto');

                    $saldo = max(
                        $total - $pagado,
                        0
                    );

                    if (
                        $saldo <= 0
                        ||
                        $total <= 0
                    ) {
                        return null;
                    }

                    $servicio =
                        $cita
                            ->serviciosCita
                            ->first()
                            ?->servicio;

                    $estadoPago =
                        $pagado <= 0
                            ? 'PENDIENTE'
                            : (
                                $pagado < $total
                                    ? 'PARCIAL'
                                    : 'PAGADO'
                            );

                    return [
                        'tipo' =>
                            'CITA_SIMPLE',

                        'id' =>
                            'cita-'
                            .
                            $cita->id,

                        'tratamiento_paciente_id' =>
                            null,

                        'cita_id' =>
                            $cita->id,

                        'concepto' =>
                            $servicio
                                ?->nombre
                            ??
                            $cita->motivo
                            ??
                            'Cita odontológica',

                        'detalle' =>
                            'Cita simple · '
                            .
                            optional(
                                $cita
                                    ->fecha_hora_inicio
                            )->format(
                                'd/m/Y H:i'
                            ),

                        'estado_clinico' =>
                            $cita
                                ->estadoCita
                                ?->codigo,

                        'total' =>
                            round(
                                $total,
                                2
                            ),

                        'pagado' =>
                            round(
                                $pagado,
                                2
                            ),

                        'saldo' =>
                            round(
                                $saldo,
                                2
                            ),

                        'estado_pago' =>
                            $estadoPago,

                        'sesiones' =>
                            [],
                    ];
                }
            )
            ->filter()
            ->values();

        return response()->json([
            'paciente' =>
                $paciente,

            'opciones' =>
                $tratamientos
                    ->concat($citas)
                    ->values(),
        ]);
    }

public function pendientes(
    Request $request
): JsonResponse {

    $buscar =
        $request
            ->string('buscar')
            ->trim()
            ->toString();

    $tipo =
        $request
            ->string('tipo')
            ->toString();

    $estadoPago =
        $request
            ->string('estado_pago')
            ->toString();


    /*
    |--------------------------------------------------------------------------
    | TRATAMIENTOS CON SALDO
    |--------------------------------------------------------------------------
    */

    $tratamientosQuery =
        TratamientoPaciente::query()

            ->where(
                'estado',
                '!=',
                TratamientoPaciente::ESTADO_CANCELADO
            )

            ->with([
                'paciente:id,codigo,nombres,apellidos,numero_documento,telefono',
                'servicio:id,codigo,nombre',
                'tratamientoCitas.cita.estadoCita:id,codigo,nombre',
            ])

            ->when(
                $buscar,
                function ($query) use ($buscar) {

                    $query->whereHas(
                        'paciente',
                        function ($query) use ($buscar) {

                            $query->where(
                                function ($query) use ($buscar) {

                                    $query
                                        ->where(
                                            'nombres',
                                            'ilike',
                                            "%{$buscar}%"
                                        )
                                        ->orWhere(
                                            'apellidos',
                                            'ilike',
                                            "%{$buscar}%"
                                        )
                                        ->orWhere(
                                            'numero_documento',
                                            'ilike',
                                            "%{$buscar}%"
                                        )
                                        ->orWhere(
                                            'codigo',
                                            'ilike',
                                            "%{$buscar}%"
                                        );
                                }
                            );
                        }
                    );
                }
            )

            ->orderByDesc('id');


    $tratamientos =
        in_array(
            $tipo,
            [
                '',
                'TRATAMIENTO',
            ],
            true
        )
            ? $tratamientosQuery
                ->get()
                ->map(
                    function (
                        TratamientoPaciente $tratamiento
                    ) {

                        $total =
                            $tratamiento
                                ->totalTratamiento();

                        $pagado =
                            $tratamiento
                                ->totalPagado();

                        $saldo =
                            $tratamiento
                                ->saldoPendiente();


                        if (
                            $saldo <= 0
                            ||
                            $total <= 0
                        ) {
                            return null;
                        }


                        $estadoPago =
                            $tratamiento
                                ->estadoPago();


                        $sesiones =
                            $tratamiento
                                ->tratamientoCitas
                                ->sortBy(
                                    'numero_sesion'
                                )
                                ->values();


                        $proximaSesion =
                            $sesiones
                                ->first(
                                    function ($relacion) {

                                        return in_array(
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
                                        );
                                    }
                                );


                        $ultimaSesion =
                            $sesiones
                                ->last();


                        $fechaReferencia =
                            $proximaSesion
                                ?->cita
                                ?->fecha_hora_inicio
                            ??
                            $ultimaSesion
                                ?->cita
                                ?->fecha_hora_inicio;


                        $progreso =
                            $total > 0
                                ? min(
                                    100,
                                    round(
                                        (
                                            $pagado
                                            /
                                            $total
                                        )
                                        *
                                        100
                                    )
                                )
                                : 0;


                        return [
                            'id' =>
                                'tratamiento-'
                                .
                                $tratamiento->id,

                            'tipo' =>
                                'TRATAMIENTO',

                            'tratamiento_paciente_id' =>
                                $tratamiento->id,

                            'cita_id' =>
                                null,

                            'paciente' =>
                                $tratamiento
                                    ->paciente,

                            'concepto' =>
                                $tratamiento
                                    ->servicio
                                    ?->nombre
                                ??
                                'Tratamiento odontológico',

                            'detalle' =>
                                count(
                                    $sesiones
                                )
                                .
                                (
                                    count(
                                        $sesiones
                                    )
                                    === 1
                                        ? ' sesión'
                                        : ' sesiones'
                                )
                                .
                                ' · '
                                .
                                $tratamiento->estado,

                            'estado_clinico' =>
                                $tratamiento->estado,

                            'estado_pago' =>
                                $estadoPago,

                            'total' =>
                                round(
                                    $total,
                                    2
                                ),

                            'pagado' =>
                                round(
                                    $pagado,
                                    2
                                ),

                            'saldo' =>
                                round(
                                    $saldo,
                                    2
                                ),

                            'progreso' =>
                                $progreso,

                            'fecha_referencia' =>
                                $fechaReferencia,

                            'sesiones_count' =>
                                count(
                                    $sesiones
                                ),
                        ];
                    }
                )
                ->filter()
                ->values()
            : collect();


    /*
    |--------------------------------------------------------------------------
    | CITAS SIMPLES CON SALDO
    |--------------------------------------------------------------------------
    */

    $citasQuery =
        Cita::query()

            ->whereDoesntHave(
                'tratamientoCita'
            )

            ->whereHas(
                'estadoCita',
                function ($query) {

                    $query->whereNotIn(
                        'codigo',
                        [
                            'CANCELADA',
                            'REPROGRAMADA',
                            'NO_ASISTIO',
                        ]
                    );
                }
            )

            ->with([
                'paciente:id,codigo,nombres,apellidos,numero_documento,telefono',
                'estadoCita:id,codigo,nombre',
                'serviciosCita.servicio:id,codigo,nombre',
            ])

            ->when(
                $buscar,
                function ($query) use ($buscar) {

                    $query->whereHas(
                        'paciente',
                        function ($query) use ($buscar) {

                            $query->where(
                                function ($query) use ($buscar) {

                                    $query
                                        ->where(
                                            'nombres',
                                            'ilike',
                                            "%{$buscar}%"
                                        )
                                        ->orWhere(
                                            'apellidos',
                                            'ilike',
                                            "%{$buscar}%"
                                        )
                                        ->orWhere(
                                            'numero_documento',
                                            'ilike',
                                            "%{$buscar}%"
                                        )
                                        ->orWhere(
                                            'codigo',
                                            'ilike',
                                            "%{$buscar}%"
                                        );
                                }
                            );
                        }
                    );
                }
            )

            ->orderByDesc(
                'fecha_hora_inicio'
            );


    $citas =
        in_array(
            $tipo,
            [
                '',
                'CITA_SIMPLE',
            ],
            true
        )
            ? $citasQuery
                ->get()
                ->map(
                    function (
                        Cita $cita
                    ) {

                        $total =
                            (float)
                            $cita
                                ->serviciosCita
                                ->sum('total');


                        if (
                            $total <= 0
                        ) {
                            return null;
                        }


                        $pagado =
                            (float)
                            Pago::query()
                                ->where(
                                    'cita_id',
                                    $cita->id
                                )
                                ->whereNull(
                                    'tratamiento_paciente_id'
                                )
                                ->where(
                                    'estado',
                                    Pago::ESTADO_REGISTRADO
                                )
                                ->sum('monto');


                        $saldo =
                            max(
                                $total
                                -
                                $pagado,
                                0
                            );


                        if (
                            $saldo <= 0
                        ) {
                            return null;
                        }


                        $estadoPago =
                            $pagado <= 0
                                ? 'PENDIENTE'
                                : 'PARCIAL';


                        $servicio =
                            $cita
                                ->serviciosCita
                                ->first()
                                ?->servicio;


                        $progreso =
                            $total > 0
                                ? min(
                                    100,
                                    round(
                                        (
                                            $pagado
                                            /
                                            $total
                                        )
                                        *
                                        100
                                    )
                                )
                                : 0;


                        return [
                            'id' =>
                                'cita-'
                                .
                                $cita->id,

                            'tipo' =>
                                'CITA_SIMPLE',

                            'tratamiento_paciente_id' =>
                                null,

                            'cita_id' =>
                                $cita->id,

                            'paciente' =>
                                $cita
                                    ->paciente,

                            'concepto' =>
                                $servicio
                                    ?->nombre
                                ??
                                $cita->motivo
                                ??
                                'Cita odontológica',

                            'detalle' =>
                                'Cita simple · '
                                .
                                (
                                    $cita
                                        ->estadoCita
                                        ?->codigo
                                    ??
                                    'SIN ESTADO'
                                ),

                            'estado_clinico' =>
                                $cita
                                    ->estadoCita
                                    ?->codigo,

                            'estado_pago' =>
                                $estadoPago,

                            'total' =>
                                round(
                                    $total,
                                    2
                                ),

                            'pagado' =>
                                round(
                                    $pagado,
                                    2
                                ),

                            'saldo' =>
                                round(
                                    $saldo,
                                    2
                                ),

                            'progreso' =>
                                $progreso,

                            'fecha_referencia' =>
                                $cita
                                    ->fecha_hora_inicio,

                            'sesiones_count' =>
                                0,
                        ];
                    }
                )
                ->filter()
                ->values()
            : collect();


    $deudas =
        $tratamientos
            ->concat(
                $citas
            )

            ->when(
                in_array(
                    $estadoPago,
                    [
                        'PENDIENTE',
                        'PARCIAL',
                    ],
                    true
                ),
                fn ($items) =>
                    $items
                        ->where(
                            'estado_pago',
                            $estadoPago
                        )
                        ->values()
            )

            ->sortByDesc(
                'saldo'
            )

            ->values();


    $resumen = [
        'cuentas' =>
            $deudas
                ->count(),

        'pacientes' =>
            $deudas
                ->pluck(
                    'paciente.id'
                )
                ->unique()
                ->count(),

        'saldo_total' =>
            round(
                (float)
                $deudas
                    ->sum(
                        'saldo'
                    ),
                2
            ),

        'pendientes' =>
            $deudas
                ->where(
                    'estado_pago',
                    'PENDIENTE'
                )
                ->count(),

        'parciales' =>
            $deudas
                ->where(
                    'estado_pago',
                    'PARCIAL'
                )
                ->count(),
    ];


    return response()->json([
        'data' =>
            $deudas,

        'resumen' =>
            $resumen,
    ]);
}

    public function store(
        GuardarPagoRequest $request
    ): RedirectResponse {

        $datos = $request->validated();

        DB::transaction(
            function () use ($datos) {
                if (
                    $datos['tipo_origen']
                    ===
                    'TRATAMIENTO'
                ) {
                    $this->guardarPagoTratamiento(
                        $datos
                    );

                    return;
                }

                $this->guardarPagoCitaSimple(
                    $datos
                );
            }
        );

        return back()->with(
            'success',
            'Pago registrado correctamente.'
        );
    }

    public function anular(
        AnularPagoRequest $request,
        Pago $pago
    ): RedirectResponse {

        DB::transaction(
            function () use (
                $request,
                $pago
            ) {
                $pago = Pago::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $pago->id
                    );

                if (
                    $pago->estado
                    ===
                    Pago::ESTADO_ANULADO
                ) {
                    throw ValidationException::withMessages([
                        'pago' =>
                            'Este pago ya se encuentra anulado.',
                    ]);
                }

                $pago->update([
                    'estado' =>
                        Pago::ESTADO_ANULADO,

                    'motivo_anulacion' =>
                        $request
                            ->motivo_anulacion,

                    'fecha_anulacion' =>
                        now(),

                    'usuario_anulacion_id' =>
                        Auth::id(),
                ]);
            }
        );

        return back()->with(
            'success',
            'Pago anulado correctamente.'
        );
    }

    private function guardarPagoTratamiento(
        array $datos
    ): Pago {

        $tratamiento = TratamientoPaciente::query()
            ->with([
                'tratamientoCitas',
            ])
            ->lockForUpdate()
            ->findOrFail(
                $datos[
                    'tratamiento_paciente_id'
                ]
            );

        if (
            $tratamiento->estado
            ===
            TratamientoPaciente::ESTADO_CANCELADO
        ) {
            throw ValidationException::withMessages([
                'tratamiento_paciente_id' =>
                    'No se pueden registrar pagos en un tratamiento cancelado.',
            ]);
        }

        $citaIds = $tratamiento
            ->tratamientoCitas
            ->pluck('cita_id');

        $pagosQuery = Pago::query()
            ->where(
                'paciente_id',
                $tratamiento
                    ->paciente_id
            )
            ->where(
                'estado',
                Pago::ESTADO_REGISTRADO
            )
            ->where(
                function ($query) use (
                    $tratamiento,
                    $citaIds
                ) {
                    $query->where(
                        'tratamiento_paciente_id',
                        $tratamiento->id
                    );

                    if (
                        $citaIds->isNotEmpty()
                    ) {
                        $query->orWhereIn(
                            'cita_id',
                            $citaIds
                        );
                    }
                }
            );

        $pagosExistentes = $pagosQuery
            ->lockForUpdate()
            ->get();

        $pagado =
            (float)
            $pagosExistentes
                ->sum('monto');

        $total =
            (float)
            $tratamiento
                ->precio_acordado;

        $saldo = max(
            $total - $pagado,
            0
        );

        $monto =
            (float)
            $datos['monto'];

        if ($saldo <= 0) {
            throw ValidationException::withMessages([
                'monto' =>
                    'Este tratamiento ya se encuentra completamente pagado.',
            ]);
        }

        if (
            $monto
            >
            round(
                $saldo,
                2
            )
        ) {
            throw ValidationException::withMessages([
                'monto' =>
                    'El monto no puede superar el saldo pendiente de S/ '
                    .
                    number_format(
                        $saldo,
                        2
                    )
                    .
                    '.',
            ]);
        }

        $citaId =
            $datos['cita_id']
            ?? null;

        if (
            $citaId
            &&
            !$citaIds->contains(
                (int)
                $citaId
            )
        ) {
            throw ValidationException::withMessages([
                'cita_id' =>
                    'La cita indicada no pertenece a este tratamiento.',
            ]);
        }

        return Pago::create([
            'paciente_id' =>
                $tratamiento
                    ->paciente_id,

            'cita_id' =>
                $citaId,

            'tratamiento_paciente_id' =>
                $tratamiento->id,

            'metodo_pago_id' =>
                $datos[
                    'metodo_pago_id'
                ],

            'monto' =>
                $monto,

            'fecha_pago' =>
                $datos[
                    'fecha_pago'
                ],

            'numero_operacion' =>
                $datos[
                    'numero_operacion'
                ]
                ?? null,
            'observaciones' =>
                $datos[
                    'observaciones'
                ]
                ?? null,

            'estado' =>
                Pago::ESTADO_REGISTRADO,

            'usuario_registro_id' =>
                Auth::id(),
        ]);
    }

    private function guardarPagoCitaSimple(
        array $datos
    ): Pago {

        $cita = Cita::query()
            ->with([
                'serviciosCita',
                'tratamientoCita',
                'estadoCita',
            ])
            ->lockForUpdate()
            ->findOrFail(
                $datos[
                    'cita_id'
                ]
            );

        if ($cita->tratamientoCita) {
            throw ValidationException::withMessages([
                'cita_id' =>
                    'Esta cita pertenece a un tratamiento. Registra el pago sobre el tratamiento.',
            ]);
        }

        if (
            in_array(
                $cita
                    ->estadoCita
                    ?->codigo,
                [
                    'CANCELADA',
                    'REPROGRAMADA',
                    'NO_ASISTIO',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'cita_id' =>
                    'Esta cita no admite pagos en su estado actual.',
            ]);
        }

        $total =
            (float)
            $cita
                ->serviciosCita
                ->sum('total');

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'cita_id' =>
                    'La cita no tiene un monto pendiente válido.',
            ]);
        }

        $pagosExistentes = Pago::query()
            ->where(
                'cita_id',
                $cita->id
            )
            ->whereNull(
                'tratamiento_paciente_id'
            )
            ->where(
                'estado',
                Pago::ESTADO_REGISTRADO
            )
            ->lockForUpdate()
            ->get();

        $pagado =
            (float)
            $pagosExistentes
                ->sum('monto');

        $saldo = max(
            $total - $pagado,
            0
        );

        $monto =
            (float)
            $datos['monto'];

        if ($saldo <= 0) {
            throw ValidationException::withMessages([
                'monto' =>
                    'Esta cita ya se encuentra completamente pagada.',
            ]);
        }

        if (
            $monto
            >
            round(
                $saldo,
                2
            )
        ) {
            throw ValidationException::withMessages([
                'monto' =>
                    'El monto no puede superar el saldo pendiente de S/ '
                    .
                    number_format(
                        $saldo,
                        2
                    )
                    .
                    '.',
            ]);
        }

        return Pago::create([
            'paciente_id' =>
                $cita
                    ->paciente_id,

            'cita_id' =>
                $cita->id,

            'tratamiento_paciente_id' =>
                null,

            'metodo_pago_id' =>
                $datos[
                    'metodo_pago_id'
                ],

            'monto' =>
                $monto,

            'fecha_pago' =>
                $datos[
                    'fecha_pago'
                ],

            'numero_operacion' =>
                $datos[
                    'numero_operacion'
                ]
                ?? null,

            'observaciones' =>
                $datos[
                    'observaciones'
                ]
                ?? null,

            'estado' =>
                Pago::ESTADO_REGISTRADO,

            'usuario_registro_id' =>
                Auth::id(),
        ]);
    }

    private function conceptoPago(
        Pago $pago
    ): string {

        if ($pago->tratamientoPaciente) {
            return $pago
                ->tratamientoPaciente
                ->servicio
                ?->nombre
                ??
                'Tratamiento odontológico';
        }

        return $pago
            ->cita
            ?->serviciosCita
            ?->first()
            ?->servicio
            ?->nombre
            ??
            $pago
                ->cita
                ?->motivo
            ??
            'Cita odontológica';
    }
}