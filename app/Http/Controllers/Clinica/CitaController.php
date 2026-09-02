<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\StoreCitaRequest;
use App\Http\Requests\Citas\UpdateCitaRequest;
use App\Models\Cita;
use App\Models\Consultorio;
use App\Models\EstadoCita;
use App\Models\HistorialEstadoCita;
use App\Models\Profesional;
use App\Models\Servicio;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CitaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTADO DE CITAS
    |--------------------------------------------------------------------------
    */

    public function index(): Response
    {
        $buscar = request()
            ->string('buscar')
            ->trim()
            ->toString();

        $estado = request()
            ->integer('estado');

        $fecha = request()
            ->string('fecha')
            ->toString();

        $profesional = request()
            ->integer('profesional');


        $citas = Cita::query()

            ->with([
                'paciente:id,codigo,nombres,apellidos,numero_documento,telefono',

                'profesional:id,nombres,apellidos,numero_colegiatura',

                'consultorio:id,codigo,nombre',

                'estadoCita:id,codigo,nombre,es_final',

                'serviciosCita.servicio:id,codigo,nombre,duracion_estimada_minutos,precio_actual',
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
                $estado,
                fn ($query) =>
                    $query->where(
                        'estado_cita_id',
                        $estado
                    )
            )

            ->when(
                $profesional,
                fn ($query) =>
                    $query->where(
                        'profesional_id',
                        $profesional
                    )
            )

            ->when(
                $fecha,
                fn ($query) =>
                    $query->whereDate(
                        'fecha_hora_inicio',
                        $fecha
                    )
            )

            ->orderBy('fecha_hora_inicio')

            ->paginate(15)

            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | PROFESIONALES ACTIVOS
        |--------------------------------------------------------------------------
        */

        $profesionales = Profesional::query()

            ->where('activo', true)

            ->select([
                'id',
                'nombres',
                'apellidos',
                'numero_colegiatura',
            ])

            ->orderBy('apellidos')
            ->orderBy('nombres')

            ->get();


        /*
        |--------------------------------------------------------------------------
        | CONSULTORIOS ACTIVOS
        |--------------------------------------------------------------------------
        */

        $consultorios = Consultorio::query()

            ->where('activo', true)

            ->select([
                'id',
                'codigo',
                'nombre',
            ])

            ->orderBy('nombre')

            ->get();



        /*
        |--------------------------------------------------------------------------
        | ESTADOS
        |--------------------------------------------------------------------------
        */

        $estados = EstadoCita::query()

            ->where('activo', true)

            ->select([
                'id',
                'codigo',
                'nombre',
                'es_final',
            ])

            ->orderBy('id')

            ->get();


        $estadoPendiente = EstadoCita::query()

            ->where(
                'codigo',
                'PENDIENTE'
            )

            ->value('id');


        /*
        |--------------------------------------------------------------------------
        | INERTIA
        |--------------------------------------------------------------------------
        */

        return Inertia::render(
            'Citas/Index',
            [
                'citas' =>
                    $citas,

                'profesionales' =>
                    $profesionales,

                'consultorios' =>
                    $consultorios,

                'estados' =>
                    $estados,

                'estadoPendiente' =>
                    $estadoPendiente,

                'filtros' => [
                    'buscar' =>
                        $buscar,

                    'estado' =>
                        $estado,

                    'fecha' =>
                        $fecha,

                    'profesional' =>
                        $profesional,
                ],
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ESTADOS QUE OCUPAN HORARIO
    |--------------------------------------------------------------------------
    */

    private function estadoBloqueaHorario(
        int $estadoId
    ): bool {

        $codigo = EstadoCita::query()

            ->whereKey($estadoId)

            ->value('codigo');


        return in_array(
            $codigo,
            [
                'PENDIENTE',
                'CONFIRMADA',
                'EN_ATENCION',
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | BUSCAR CONFLICTO DE HORARIO
    |--------------------------------------------------------------------------
    |
    | PENDIENTE
    | CONFIRMADA
    | EN_ATENCION
    |
    | bloquean utilizando fecha_hora_fin.
    |
    | ATENDIDA utiliza fecha_hora_fin_real.
    |
    | Esto permite que si una cita estaba programada:
    |
    | 10:00 - 11:00
    |
    | pero termina realmente:
    |
    | 10:35
    |
    | el doctor quede disponible desde las 10:35.
    |
    */

    private function existeConflictoHorario(
        string $campo,
        int $valor,
        Carbon $inicio,
        Carbon $fin,
        ?Cita $citaActual = null
    ): bool {

        return Cita::query()

            ->when(
                $citaActual,
                fn ($query) =>
                    $query->where(
                        'id',
                        '<>',
                        $citaActual->id
                    )
            )

            ->where(
                $campo,
                $valor
            )

            /*
             * La cita existente debe comenzar
             * antes de que termine la nueva.
             */

            ->where(
                'fecha_hora_inicio',
                '<',
                $fin
            )

            ->where(
                function ($query) use ($inicio) {

                    /*
                     * Estados que todavía
                     * mantienen reservado el horario.
                     */

                    $query->where(
                        function ($query) use ($inicio) {

                            $query

                                ->whereHas(
                                    'estadoCita',
                                    function ($query) {

                                        $query->whereIn(
                                            'codigo',
                                            [
                                                'PENDIENTE',
                                                'CONFIRMADA',
                                                'EN_ATENCION',
                                            ]
                                        );
                                    }
                                )

                                ->where(
                                    'fecha_hora_fin',
                                    '>',
                                    $inicio
                                );
                        }
                    )

                    /*
                     * Una cita atendida solamente
                     * ocupa hasta su hora real
                     * de finalización.
                     */

                    ->orWhere(
                        function ($query) use ($inicio) {

                            $query

                                ->whereHas(
                                    'estadoCita',
                                    function ($query) {

                                        $query->where(
                                            'codigo',
                                            'ATENDIDA'
                                        );
                                    }
                                )

                                ->whereRaw(
                                    '
                                    COALESCE(
                                        fecha_hora_fin_real,
                                        fecha_hora_fin
                                    ) > ?
                                    ',
                                    [
                                        $inicio->format(
                                            'Y-m-d H:i:s'
                                        ),
                                    ]
                                );
                        }
                    );
                }
            )

            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR DISPONIBILIDAD
    |--------------------------------------------------------------------------
    */

    private function validarDisponibilidad(
        array $datos,
        ?Cita $citaActual = null
    ): void {

        $inicio = Carbon::parse(
            $datos['fecha_hora_inicio']
        );

        $fin = Carbon::parse(
            $datos['fecha_hora_fin']
        );


        /*
        |--------------------------------------------------------------------------
        | PROFESIONAL
        |--------------------------------------------------------------------------
        */

        $conflictoProfesional =
            $this->existeConflictoHorario(
                'profesional_id',
                (int) $datos['profesional_id'],
                $inicio,
                $fin,
                $citaActual
            );


        if ($conflictoProfesional) {

            throw ValidationException::withMessages([
                'fecha_hora_inicio' =>
                    'El profesional ya tiene una cita o atención durante ese horario.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTORIO
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $datos['consultorio_id']
            )
        ) {

            $conflictoConsultorio =
                $this->existeConflictoHorario(
                    'consultorio_id',
                    (int) $datos['consultorio_id'],
                    $inicio,
                    $fin,
                    $citaActual
                );


            if ($conflictoConsultorio) {

                throw ValidationException::withMessages([
                    'consultorio_id' =>
                        'El consultorio ya está ocupado durante ese horario.',
                ]);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER SERVICIO
    |--------------------------------------------------------------------------
    */

    private function obtenerServicio(
        int $servicioId
    ): Servicio {

        $servicio = Servicio::query()
            ->whereKey($servicioId)
            ->where('activo', true)
            ->first();

        if (!$servicio) {

            throw ValidationException::withMessages([
                'servicio_id' =>
                    'El servicio seleccionado no está disponible.',
            ]);
        }

        return $servicio;
    }


    /*
    |--------------------------------------------------------------------------
    | GUARDAR SERVICIO DE LA CITA
    |--------------------------------------------------------------------------
    */

    private function guardarServicioCita(
        Cita $cita,
        Servicio $servicio
    ): void {

        /*
        * Una cita solamente tendrá
        * un servicio.
        *
        * Durante las pruebas anteriores
        * pudieron quedar varios registros.
        */

        $cita
            ->serviciosCita()
            ->delete();


        $cita
            ->serviciosCita()
            ->create([

                'servicio_id' =>
                    $servicio->id,

                'cantidad' =>
                    1,

                'precio_unitario' =>
                    $servicio->precio_actual,

                'descuento' =>
                    0,

                'total' =>
                    $servicio->precio_actual,

                'estado' =>
                    'PENDIENTE',

                'observaciones' =>
                    null,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR CITA
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreCitaRequest $request
    ): RedirectResponse {

        DB::transaction(
            function () use ($request) {

                /*
                |--------------------------------------------------------------------------
                | DATOS VALIDADOS
                |--------------------------------------------------------------------------
                */

                $datos =
                    $request->validated();


                /*
                |--------------------------------------------------------------------------
                | SERVICIO
                |--------------------------------------------------------------------------
                */

                if (
                    empty(
                        $datos['servicio_id']
                    )
                ) {

                    throw ValidationException::withMessages([
                        'servicio_id' =>
                            'Debes seleccionar un servicio.',
                    ]);
                }


                $servicioId =
                    (int)
                    $datos['servicio_id'];


                /*
                * servicio_id NO existe
                * en la tabla citas.
                */

                unset(
                    $datos['servicio_id']
                );


                $servicio =
                    $this->obtenerServicio(
                        $servicioId
                    );


                /*
                |--------------------------------------------------------------------------
                | CALCULAR HORA FINAL
                |--------------------------------------------------------------------------
                */

                $inicio =
                    Carbon::parse(
                        $datos[
                            'fecha_hora_inicio'
                        ]
                    );


                $duracion =
                    (int)
                    $servicio
                        ->duracion_estimada_minutos;


                $datos[
                    'fecha_hora_fin'
                ] =
                    $inicio
                        ->copy()
                        ->addMinutes(
                            $duracion
                        );


                /*
                |--------------------------------------------------------------------------
                | ESTADO INICIAL
                |--------------------------------------------------------------------------
                */

                if (
                    empty(
                        $datos[
                            'estado_cita_id'
                        ]
                    )
                ) {

                    $datos[
                        'estado_cita_id'
                    ] =
                        EstadoCita::query()

                            ->where(
                                'codigo',
                                'PENDIENTE'
                            )

                            ->value('id');
                }


                if (
                    empty(
                        $datos[
                            'estado_cita_id'
                        ]
                    )
                ) {

                    throw ValidationException::withMessages([
                        'estado_cita_id' =>
                            'No se encontró el estado PENDIENTE.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDAR DISPONIBILIDAD
                |--------------------------------------------------------------------------
                */

                if (
                    $this->estadoBloqueaHorario(
                        (int)
                        $datos[
                            'estado_cita_id'
                        ]
                    )
                ) {

                    $this->validarDisponibilidad(
                        $datos
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | USUARIO
                |--------------------------------------------------------------------------
                */

                $datos[
                    'usuario_creador_id'
                ] =
                    Auth::id();


                /*
                |--------------------------------------------------------------------------
                | CREAR CITA
                |--------------------------------------------------------------------------
                */

                $cita =
                    Cita::create(
                        $datos
                    );


                /*
                |--------------------------------------------------------------------------
                | SERVICIO DE LA CITA
                |--------------------------------------------------------------------------
                */

                $this->guardarServicioCita(
                    $cita,
                    $servicio
                );


                /*
                |--------------------------------------------------------------------------
                | HISTORIAL
                |--------------------------------------------------------------------------
                */

                HistorialEstadoCita::create([

                    'cita_id' =>
                        $cita->id,

                    'estado_anterior_id' =>
                        null,

                    'estado_nuevo_id' =>
                        $cita
                            ->estado_cita_id,

                    'usuario_id' =>
                        Auth::id(),

                    'motivo' =>
                        'Creación inicial de la cita',

                    'fecha_cambio' =>
                        now(),
                ]);
            }
        );


        return back()->with(
            'success',
            'Cita registrada correctamente.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR CITA
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateCitaRequest $request,
        Cita $cita
    ): RedirectResponse {

        DB::transaction(
            function () use (
                $request,
                $cita
            ) {

                $datos =
                    $request->validated();


                /*
                |--------------------------------------------------------------------------
                | SERVICIO
                |--------------------------------------------------------------------------
                */

                $servicioId =
                    (int) (
                        $datos[
                            'servicio_id'
                        ]
                        ??
                        $cita
                            ->serviciosCita()
                            ->value(
                                'servicio_id'
                            )
                    );


                if (!$servicioId) {

                    throw ValidationException::withMessages([
                        'servicio_id' =>
                            'Debes seleccionar un servicio.',
                    ]);
                }


                unset(
                    $datos['servicio_id']
                );


                $servicio =
                    $this->obtenerServicio(
                        $servicioId
                    );


                /*
                |--------------------------------------------------------------------------
                | RECALCULAR HORARIO
                |--------------------------------------------------------------------------
                */

                $inicio =
                    Carbon::parse(
                        $datos[
                            'fecha_hora_inicio'
                        ]
                        ??
                        $cita
                            ->fecha_hora_inicio
                    );


                $duracion =
                    (int)
                    $servicio
                        ->duracion_estimada_minutos;


                $datos[
                    'fecha_hora_fin'
                ] =
                    $inicio
                        ->copy()
                        ->addMinutes(
                            $duracion
                        );


                /*
                |--------------------------------------------------------------------------
                | ESTADO
                |--------------------------------------------------------------------------
                */

                $estadoAnterior =
                    (int)
                    $cita
                        ->estado_cita_id;


                $estadoNuevoId =
                    (int) (
                        $datos[
                            'estado_cita_id'
                        ]
                        ??
                        $estadoAnterior
                    );


                $estadoNuevo =
                    EstadoCita::findOrFail(
                        $estadoNuevoId
                    );


                /*
                * CANCELADA se controla
                * mediante cancelar().
                */

                if (
                    $estadoNuevo->codigo ===
                    'CANCELADA'
                    &&
                    $estadoAnterior !==
                    $estadoNuevoId
                ) {

                    throw ValidationException::withMessages([
                        'estado_cita_id' =>
                            'Utiliza la acción Cancelar cita.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | DISPONIBILIDAD
                |--------------------------------------------------------------------------
                */

                $datosDisponibilidad = [

                    'profesional_id' =>
                        $datos[
                            'profesional_id'
                        ]
                        ??
                        $cita
                            ->profesional_id,

                    'consultorio_id' =>
                        array_key_exists(
                            'consultorio_id',
                            $datos
                        )
                            ? $datos[
                                'consultorio_id'
                            ]
                            : $cita
                                ->consultorio_id,

                    'fecha_hora_inicio' =>
                        $inicio,

                    'fecha_hora_fin' =>
                        $datos[
                            'fecha_hora_fin'
                        ],
                ];


                if (
                    $this->estadoBloqueaHorario(
                        $estadoNuevoId
                    )
                ) {

                    $this->validarDisponibilidad(
                        $datosDisponibilidad,
                        $cita
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | TERMINÓ LA ATENCIÓN
                |--------------------------------------------------------------------------
                */

                if (
                    $estadoNuevo->codigo ===
                    'ATENDIDA'
                    &&
                    $estadoAnterior !==
                    $estadoNuevoId
                ) {

                    $datos[
                        'fecha_hora_fin_real'
                    ] =
                        now();
                }


                /*
                |--------------------------------------------------------------------------
                | ESTADO ACTIVO
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $estadoNuevo->codigo,
                        [
                            'PENDIENTE',
                            'CONFIRMADA',
                            'EN_ATENCION',
                        ],
                        true
                    )
                ) {

                    $datos[
                        'fecha_hora_fin_real'
                    ] =
                        null;

                    $datos[
                        'fecha_cancelacion'
                    ] =
                        null;

                    $datos[
                        'motivo_cancelacion'
                    ] =
                        null;
                }


                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR
                |--------------------------------------------------------------------------
                */

                $cita->update(
                    $datos
                );


                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR SERVICIO
                |--------------------------------------------------------------------------
                */

                $this->guardarServicioCita(
                    $cita,
                    $servicio
                );


                /*
                |--------------------------------------------------------------------------
                | HISTORIAL
                |--------------------------------------------------------------------------
                */

                if (
                    $estadoAnterior !==
                    (int)
                    $cita
                        ->estado_cita_id
                ) {

                    HistorialEstadoCita::create([

                        'cita_id' =>
                            $cita->id,

                        'estado_anterior_id' =>
                            $estadoAnterior,

                        'estado_nuevo_id' =>
                            $cita
                                ->estado_cita_id,

                        'usuario_id' =>
                            Auth::id(),

                        'motivo' =>
                            'Cambio de estado de la cita',

                        'fecha_cambio' =>
                            now(),
                    ]);
                }
            }
        );


        return back()->with(
            'success',
            'Cita actualizada correctamente.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CAMBIAR ESTADO
    |--------------------------------------------------------------------------
    */

    public function cambiarEstado(
        Cita $cita
    ): RedirectResponse {

        $datos = request()->validate([
            'estado_cita_id' => [
                'required',
                'integer',
                'exists:estados_cita,id',
            ],

            'motivo' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);


        $nuevoEstado =
            EstadoCita::findOrFail(
                $datos['estado_cita_id']
            );


        /*
         * CANCELADA tiene una operación
         * específica porque debe guardar
         * motivo y fecha de cancelación.
         */

        if (
            $nuevoEstado->codigo ===
            'CANCELADA'
        ) {

            return back()->with(
                'warning',
                'Para cancelar una cita utiliza la acción Cancelar cita.'
            );
        }


        /*
         * Si estamos intentando volver a
         * ocupar horario, comprobar disponibilidad.
         */

        if (
            in_array(
                $nuevoEstado->codigo,
                [
                    'PENDIENTE',
                    'CONFIRMADA',
                    'EN_ATENCION',
                ],
                true
            )
        ) {

            $this->validarDisponibilidad(
                [
                    'profesional_id' =>
                        $cita->profesional_id,

                    'consultorio_id' =>
                        $cita->consultorio_id,

                    'fecha_hora_inicio' =>
                        $cita->fecha_hora_inicio,

                    'fecha_hora_fin' =>
                        $cita->fecha_hora_fin,
                ],
                $cita
            );
        }


        DB::transaction(
            function () use (
                $cita,
                $datos,
                $nuevoEstado
            ) {

                $estadoAnterior =
                    $cita->estado_cita_id;


                /*
                 * No generar historia duplicada.
                 */

                if (
                    $estadoAnterior ===
                    $nuevoEstado->id
                ) {
                    return;
                }


                $actualizacion = [
                    'estado_cita_id' =>
                        $nuevoEstado->id,
                ];


                /*
                 * Cuando finaliza la atención
                 * guardamos la hora REAL.
                 */

                if (
                    $nuevoEstado->codigo ===
                    'ATENDIDA'
                ) {

                    $actualizacion[
                        'fecha_hora_fin_real'
                    ] = now();
                }


                /*
                 * Si vuelve a un estado activo,
                 * ya no está finalizada.
                 */

                if (
                    in_array(
                        $nuevoEstado->codigo,
                        [
                            'PENDIENTE',
                            'CONFIRMADA',
                            'EN_ATENCION',
                        ],
                        true
                    )
                ) {

                    $actualizacion[
                        'fecha_hora_fin_real'
                    ] = null;

                    $actualizacion[
                        'fecha_cancelacion'
                    ] = null;

                    $actualizacion[
                        'motivo_cancelacion'
                    ] = null;
                }


                $cita->update(
                    $actualizacion
                );


                HistorialEstadoCita::create([
                    'cita_id' =>
                        $cita->id,

                    'estado_anterior_id' =>
                        $estadoAnterior,

                    'estado_nuevo_id' =>
                        $nuevoEstado->id,

                    'usuario_id' =>
                        Auth::id(),

                    'motivo' =>
                        $datos['motivo']
                        ?? null,

                    'fecha_cambio' =>
                        now(),
                ]);
            }
        );


        return back()->with(
            'success',
            'Estado de la cita actualizado correctamente.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CANCELAR CITA
    |--------------------------------------------------------------------------
    */

    public function cancelar(
        Cita $cita
    ): RedirectResponse {

        $datos = request()->validate([
            'motivo_cancelacion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);


        $estadoCancelada =
            EstadoCita::query()

                ->where(
                    'codigo',
                    'CANCELADA'
                )

                ->firstOrFail();


        /*
         * Ya está cancelada.
         */

        if (
            $cita->estado_cita_id ===
            $estadoCancelada->id
        ) {

            return back()->with(
                'warning',
                'La cita ya se encuentra cancelada.'
            );
        }


        DB::transaction(
            function () use (
                $cita,
                $datos,
                $estadoCancelada
            ) {

                $estadoAnterior =
                    $cita->estado_cita_id;


                $cita->update([
                    'estado_cita_id' =>
                        $estadoCancelada->id,

                    'fecha_cancelacion' =>
                        now(),

                    'motivo_cancelacion' =>
                        $datos[
                            'motivo_cancelacion'
                        ] ?? null,
                ]);


                HistorialEstadoCita::create([
                    'cita_id' =>
                        $cita->id,

                    'estado_anterior_id' =>
                        $estadoAnterior,

                    'estado_nuevo_id' =>
                        $estadoCancelada->id,

                    'usuario_id' =>
                        Auth::id(),

                    'motivo' =>
                        $datos[
                            'motivo_cancelacion'
                        ]
                        ?? 'Cita cancelada',

                    'fecha_cambio' =>
                        now(),
                ]);
            }
        );


        return back()->with(
            'success',
            'Cita cancelada correctamente.'
        );
    }
}