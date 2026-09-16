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
use App\Support\Clinica\AlcanceClinico;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\ReprogramarCitaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\TratamientoPaciente;
use App\Models\TratamientoCita;
use App\Http\Requests\Citas\FinalizarAtencionRequest;
use Illuminate\Validation\Rule;

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

        $usuario = request()->user();
        $profesionalId = AlcanceClinico::profesionalId($usuario);

        if ($profesionalId !== null) {
            $profesional = $profesionalId;
        }


        $citas = AlcanceClinico::citas(
            Cita::query(),
            $usuario
        )

            ->with([
                'paciente:id,codigo,tipo_documento,numero_documento,nombres,apellidos,telefono,correo',

                'profesional:id,nombres,apellidos,numero_colegiatura',

                'consultorio:id,codigo,nombre',

                'estadoCita:id,codigo,nombre,es_final',

                'serviciosCita.servicio:id,codigo,nombre,duracion_estimada_minutos,precio_actual',

                'historialEstados' => function ($query) {

                    $query
                        ->with([
                            'estadoAnterior:id,codigo,nombre',
                            'estadoNuevo:id,codigo,nombre',
                        ])
                        ->orderBy(
                            'fecha_cambio',
                            'asc'
                        );

                },

                'citaOrigen:id,cita_origen_id,fecha_hora_inicio,fecha_hora_fin,estado_cita_id',

                'citasReprogramadas:id,cita_origen_id,fecha_hora_inicio,fecha_hora_fin,estado_cita_id',

                'tratamientoCita.tratamientoPaciente.servicio',
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

            ->when(
                $profesionalId !== null,
                fn ($query) => $query->whereKey($profesionalId)
            )

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

    public function agenda(
        Request $request
    ): JsonResponse {

        $profesionalId = AlcanceClinico::profesionalId(
            $request->user()
        );

        if ($profesionalId !== null) {
            $request->merge([
                'profesional_id' => $profesionalId,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validación
        |--------------------------------------------------------------------------
        */

        $datos = $request->validate([

            'fecha' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'profesional_id' => [
                'nullable',
                'integer',
                'exists:profesionales,id',
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Fecha
        |--------------------------------------------------------------------------
        */

        $zonaHoraria =
            config(
                'app.timezone',
                'America/Lima'
            );


        $fecha =
            $datos['fecha']
            ?? now(
                $zonaHoraria
            )->format('Y-m-d');


        $inicioDia =
            Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $fecha . ' 00:00:00',
                $zonaHoraria
            );


        $finDia =
            $inicioDia
                ->copy()
                ->endOfDay();


        /*
        |--------------------------------------------------------------------------
        | Consulta
        |--------------------------------------------------------------------------
        */

        $citas = AlcanceClinico::citas(
            Cita::query(),
            $request->user()
        )

            ->with([

                'paciente:id,codigo,nombres,apellidos,numero_documento,telefono',

                'profesional:id,nombres,apellidos,numero_colegiatura',

                'consultorio:id,codigo,nombre',

                'estadoCita:id,codigo,nombre,es_final',

                'serviciosCita.servicio:id,codigo,nombre,duracion_estimada_minutos,precio_actual',

                'historialEstados' => function ($query) {

                    $query
                        ->with([
                            'estadoAnterior:id,codigo,nombre',
                            'estadoNuevo:id,codigo,nombre',
                        ])
                        ->orderBy(
                            'fecha_cambio',
                            'asc'
                        );

                },

                'citaOrigen',

                'citasReprogramadas',

                'tratamientoCita.tratamientoPaciente.servicio',

            ])

            ->whereBetween(
                'fecha_hora_inicio',
                [
                    $inicioDia,
                    $finDia,
                ]
            )

            /*
            |--------------------------------------------------------------------------
            | Estados que queremos mostrar en agenda
            |--------------------------------------------------------------------------
            |
            | CANCELADA, NO_ASISTIO y REPROGRAMADA ya no ocupan horario.
            |
            */

            ->whereHas(
                'estadoCita',
                function ($query) {

                    $query->whereIn(
                        'codigo',
                        [
                            'PENDIENTE',
                            'CONFIRMADA',
                            'EN_ATENCION',
                            'ATENDIDA',
                        ]
                    );

                }
            )

            ->when(
                !empty(
                    $datos['profesional_id']
                ),

                function ($query) use ($datos) {

                    $query->where(
                        'profesional_id',
                        $datos['profesional_id']
                    );

                }
            )

            ->orderBy(
                'fecha_hora_inicio'
            )

            ->get();


        /*
        |--------------------------------------------------------------------------
        | Datos específicos para dibujar la agenda
        |--------------------------------------------------------------------------
        */

        $citas->each(
            function (Cita $cita) use (
                $zonaHoraria
            ) {

                $inicio =
                    $cita
                        ->fecha_hora_inicio
                        ->copy()
                        ->timezone(
                            $zonaHoraria
                        );


                $finBase =
                    $cita->fecha_hora_fin_real
                    ?? $cita->fecha_hora_fin;


                $fin =
                    $finBase
                        ?->copy()
                        ->timezone(
                            $zonaHoraria
                        );


                $cita->setAttribute(
                    'agenda_inicio_minutos',

                    (
                        $inicio->hour * 60
                    )
                    +
                    $inicio->minute
                );


                $cita->setAttribute(
                    'agenda_fin_minutos',

                    $fin
                        ? (
                            ($fin->hour * 60)
                            +
                            $fin->minute
                        )
                        : (
                            ($inicio->hour * 60)
                            +
                            $inicio->minute
                            +
                            30
                        )
                );


                $cita->setAttribute(
                    'agenda_inicio_texto',
                    $inicio->format('H:i')
                );


                $cita->setAttribute(
                    'agenda_fin_texto',

                    $fin
                        ? $fin->format('H:i')
                        : null
                );

            }
        );


        return response()->json([

            'fecha' =>
                $fecha,

            'citas' =>
                $citas,

        ]);
    }

    public function disponibilidad(
        Request $request
    ): JsonResponse {

        $profesionalId = AlcanceClinico::profesionalId(
            $request->user()
        );

        if ($profesionalId !== null) {
            $request->merge([
                'profesional_id' => $profesionalId,
            ]);
        }

        $datos = $request->validate([

            'fecha' => [
                'required',
                'date_format:Y-m-d',
            ],

            'profesional_id' => [
                'required',
                'integer',

                Rule::exists(
                    'profesionales',
                    'id'
                )->where(
                    'activo',
                    true
                ),
            ],

            'consultorio_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'consultorios',
                    'id'
                )->where(
                    'activo',
                    true
                ),
            ],

            'servicio_id' => [
                'required',
                'integer',

                Rule::exists(
                    'servicios',
                    'id'
                )->where(
                    'activo',
                    true
                ),
            ],

            'cita_id' => [
                'nullable',
                'integer',
                'exists:citas,id',
            ],
        ]);


        $servicio = Servicio::query()
            ->whereKey(
                $datos['servicio_id']
            )
            ->where(
                'activo',
                true
            )
            ->firstOrFail();


        $zonaHoraria = config(
            'app.timezone',
            'America/Lima'
        );


        /*
        |--------------------------------------------------------------------------
        | Horario provisional de la clínica
        |--------------------------------------------------------------------------
        |
        | Más adelante esto podrá salir de Configuración.
        |
        */

        $inicioJornada = Carbon::createFromFormat(
            'Y-m-d H:i',
            $datos['fecha'] . ' 08:00',
            $zonaHoraria
        );


        $finJornada = Carbon::createFromFormat(
            'Y-m-d H:i',
            $datos['fecha'] . ' 20:00',
            $zonaHoraria
        );


        $citaActual = null;


        if (
            !empty(
                $datos['cita_id']
            )
        ) {

            $citaActual = Cita::find(
                $datos['cita_id']
            );

            if ($citaActual !== null) {
                AlcanceClinico::autorizarCita(
                    $request->user(),
                    $citaActual
                );
            }
        }


        $horarios = [];

        $cursor = $inicioJornada
            ->copy();


        /*
        |--------------------------------------------------------------------------
        | Sugerencias cada 15 minutos
        |--------------------------------------------------------------------------
        |
        | Esto NO obliga al usuario a utilizar múltiplos de 15.
        |
        */

        while (
            $cursor->lt(
                $finJornada
            )
        ) {

            $inicio = $cursor
                ->copy();


            $fin = $inicio
                ->copy()
                ->addMinutes(
                    $servicio
                        ->duracion_estimada_minutos
                );


            /*
            |--------------------------------------------------------------------------
            | No mostrar una cita que terminaría después del cierre
            |--------------------------------------------------------------------------
            */

            if (
                $fin->gt(
                    $finJornada
                )
            ) {

                $cursor->addMinutes(
                    15
                );

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | No sugerir horas pasadas
            |--------------------------------------------------------------------------
            */

            if (
                $inicio->lt(
                    now(
                        $zonaHoraria
                    )
                )
            ) {

                $cursor->addMinutes(
                    15
                );

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Profesional
            |--------------------------------------------------------------------------
            */

            $profesionalOcupado =
                $this->existeConflictoHorario(
                    'profesional_id',
                    $datos['profesional_id'],
                    $inicio,
                    $fin,
                    $citaActual
                );


            /*
            |--------------------------------------------------------------------------
            | Consultorio
            |--------------------------------------------------------------------------
            */

            $consultorioOcupado =
                false;


            if (
                !empty(
                    $datos['consultorio_id']
                )
            ) {

                $consultorioOcupado =
                    $this->existeConflictoHorario(
                        'consultorio_id',
                        $datos['consultorio_id'],
                        $inicio,
                        $fin,
                        $citaActual
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Disponible
            |--------------------------------------------------------------------------
            */

            if (
                !$profesionalOcupado
                &&
                !$consultorioOcupado
            ) {

                $horarios[] = [

                    'hora' =>
                        $inicio->format(
                            'H:i'
                        ),

                    'inicio' =>
                        $inicio->format(
                            'Y-m-d\TH:i'
                        ),

                    'fin' =>
                        $fin->format(
                            'H:i'
                        ),

                ];
            }


            $cursor->addMinutes(
                15
            );
        }


        return response()->json([

            'fecha' =>
                $datos['fecha'],

            'duracion' =>
                $servicio
                    ->duracion_estimada_minutos,

            'horarios' =>
                $horarios,

        ]);
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

                            ->value(
                                'id'
                            );
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
                | PREPARAR TRATAMIENTO
                |--------------------------------------------------------------------------
                |
                | Aquí todavía conservamos:
                |
                | servicio_id
                | tipo_atencion
                | tratamiento_paciente_id
                | precio_acordado
                |
                | porque prepararTratamiento() necesita esos datos.
                |
                */

                $tratamiento =
                    null;


                $numeroSesion =
                    null;


                /*
                 * Una CITA_SIMPLE no debe crear tratamiento.
                 */
                if (
                    $datos['tipo_atencion']
                    !==
                    'CITA_SIMPLE'
                ) {

                    $datosTratamiento =
                        $this->prepararTratamiento(
                            $datos
                        );


                    /** @var TratamientoPaciente $tratamiento */
                    $tratamiento =
                        $datosTratamiento[
                            'tratamiento'
                        ];


                    $numeroSesion =
                        (int)
                        $datosTratamiento[
                            'numero_sesion'
                        ];
                }


                /*
                |--------------------------------------------------------------------------
                | QUITAR CAMPOS QUE NO PERTENECEN A CITAS
                |--------------------------------------------------------------------------
                |
                | Estos campos son utilizados para la lógica del formulario,
                | pero NO existen como columnas en la tabla citas.
                |
                */

                unset(

                    $datos[
                        'servicio_id'
                    ],

                    $datos[
                        'tipo_atencion'
                    ],

                    $datos[
                        'tratamiento_paciente_id'
                    ],

                    $datos[
                        'precio_acordado'
                    ]

                );


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
                |
                | servicios_cita seguirá indicando qué servicio corresponde
                | a esta sesión.
                |
                | El precio financiero principal del tratamiento se manejará
                | mediante tratamientos_pacientes.precio_acordado.
                |
                */

                $this->guardarServicioCita(
                    $cita,
                    $servicio
                );


                /*
                |--------------------------------------------------------------------------
                | RELACIONAR CITA CON TRATAMIENTO
                |--------------------------------------------------------------------------
                */

                if (
                    $tratamiento
                ) {

                    TratamientoCita::create([

                        'tratamiento_paciente_id' =>
                            $tratamiento->id,

                        'cita_id' =>
                            $cita->id,

                        'numero_sesion' =>
                            $numeroSesion,

                        'observaciones' =>
                            null,

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | HISTORIAL DE LA CITA
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

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR CITA
                |--------------------------------------------------------------------------
                */

                $cita =
                    Cita::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $cita->id
                        );

                AlcanceClinico::autorizarCita(
                    $request->user(),
                    $cita
                );


                $datos =
                    $request
                        ->validated();


                /*
                |--------------------------------------------------------------------------
                | RELACIÓN CON TRATAMIENTO
                |--------------------------------------------------------------------------
                */

                $relacionTratamiento =
                    TratamientoCita::query()
                        ->where(
                            'cita_id',
                            $cita->id
                        )
                        ->lockForUpdate()
                        ->first();


                $tratamiento =
                    null;


                if (
                    $relacionTratamiento
                ) {

                    $tratamiento =
                        TratamientoPaciente::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $relacionTratamiento
                                    ->tratamiento_paciente_id
                            );
                }


                /*
                |--------------------------------------------------------------------------
                | SERVICIO ACTUAL
                |--------------------------------------------------------------------------
                */

                $servicioActualId =
                    (int)
                    $cita
                        ->serviciosCita()
                        ->value(
                            'servicio_id'
                        );


                $servicioSolicitadoId =
                    (int) (
                        $datos[
                            'servicio_id'
                        ]
                        ??
                        $servicioActualId
                    );


                /*
                |--------------------------------------------------------------------------
                | PROTECCIÓN DE TRATAMIENTO
                |--------------------------------------------------------------------------
                |
                | Si la cita es una sesión de tratamiento:
                |
                | - paciente NO cambia;
                | - servicio NO cambia;
                | - tratamiento NO cambia.
                |
                | Sí permitimos modificar profesional, consultorio,
                | fecha/hora, motivo y observaciones.
                |
                */

                if (
                    $tratamiento
                ) {

                    $pacienteSolicitadoId =
                        (int) (
                            $datos[
                                'paciente_id'
                            ]
                            ??
                            $cita
                                ->paciente_id
                        );


                    if (
                        $pacienteSolicitadoId
                        !==
                        (int)
                        $tratamiento
                            ->paciente_id
                    ) {

                        throw ValidationException::withMessages([

                            'paciente_id' =>
                                'No puedes cambiar el paciente porque esta cita pertenece a un tratamiento.',

                        ]);
                    }


                    if (
                        $servicioSolicitadoId
                        !==
                        (int)
                        $tratamiento
                            ->servicio_id
                    ) {

                        throw ValidationException::withMessages([

                            'servicio_id' =>
                                'No puedes cambiar el servicio porque esta cita pertenece a un tratamiento.',

                        ]);
                    }


                    /*
                    * Forzamos los valores originales aunque alguien
                    * intente alterar la petición manualmente.
                    */

                    $datos[
                        'paciente_id'
                    ] =
                        $cita
                            ->paciente_id;


                    $servicioSolicitadoId =
                        (int)
                        $tratamiento
                            ->servicio_id;
                }


                if (
                    !$servicioSolicitadoId
                ) {

                    throw ValidationException::withMessages([

                        'servicio_id' =>
                            'Debes seleccionar un servicio.',

                    ]);
                }


                unset(
                    $datos[
                        'servicio_id'
                    ]
                );


                $servicio =
                    $this->obtenerServicio(
                        $servicioSolicitadoId
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
                * Estas acciones tienen flujos especializados.
                */

                if (
                    $estadoNuevo->codigo
                    ===
                    'CANCELADA'
                    &&
                    $estadoAnterior
                    !==
                    $estadoNuevoId
                ) {

                    throw ValidationException::withMessages([

                        'estado_cita_id' =>
                            'Utiliza la acción Cancelar cita.',

                    ]);
                }


                if (
                    $estadoNuevo->codigo
                    ===
                    'ATENDIDA'
                    &&
                    $estadoAnterior
                    !==
                    $estadoNuevoId
                ) {

                    throw ValidationException::withMessages([

                        'estado_cita_id' =>
                            'Utiliza la acción Finalizar atención.',

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
                | SI CONTINÚA ACTIVA
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
                | ACTUALIZAR CITA
                |--------------------------------------------------------------------------
                */

                $cita->update(
                    $datos
                );


                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR SERVICIO SOLO EN CITA SIMPLE
                |--------------------------------------------------------------------------
                |
                | Una cita ligada a tratamiento conserva su snapshot
                | de servicios_cita. Editar la hora o profesional no debe
                | reescribir el servicio ni su precio histórico.
                |
                */

                if (
                    !$tratamiento
                ) {

                    $this->guardarServicioCita(
                        $cita,
                        $servicio
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | HISTORIAL
                |--------------------------------------------------------------------------
                */

                if (
                    $estadoAnterior
                    !==
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

        AlcanceClinico::autorizarCita(
            request()->user(),
            $cita
        );

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


        Gate::authorize(
            match ($nuevoEstado->codigo) {
                'CONFIRMADA' =>
                    'citas.confirmar',

                'EN_ATENCION' =>
                    'citas.iniciar',

                'NO_ASISTIO' =>
                    'citas.no_asistio',

                default =>
                    'citas.editar',
            }
        );


        /*
        * CANCELADA y ATENDIDA tienen operaciones especializadas.
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


        if (
            $nuevoEstado->codigo ===
            'ATENDIDA'
        ) {

            return back()->with(
                'warning',
                'Para finalizar una cita utiliza la acción Finalizar atención.'
            );
        }


        $codigoEstadoActual =
            $cita
                ->estadoCita()
                ->value('codigo');


        /*
        |--------------------------------------------------------------------------
        | NO ASISTIÓ
        |--------------------------------------------------------------------------
        |
        | Una ausencia solo tiene sentido antes de iniciar la atención.
        |
        | IMPORTANTE:
        | - NO elimina tratamiento_citas.
        | - NO cambia el estado del tratamiento.
        | - NO cuenta como sesión clínica realizada.
        | - NO altera los pagos del tratamiento.
        |
        */

        if (
            $nuevoEstado->codigo ===
            'NO_ASISTIO'
            &&
            !in_array(
                $codigoEstadoActual,
                [
                    'PENDIENTE',
                    'CONFIRMADA',
                ],
                true
            )
        ) {

            throw ValidationException::withMessages([
                'estado_cita_id' =>
                    'Solo una cita PENDIENTE o CONFIRMADA puede marcarse como NO ASISTIÓ.',
            ]);
        }


        /*
        * Los estados finales no se reabren usando esta operación genérica.
        */
        if (
            in_array(
                $codigoEstadoActual,
                [
                    'ATENDIDA',
                    'CANCELADA',
                    'NO_ASISTIO',
                    'REPROGRAMADA',
                ],
                true
            )
            &&
            $codigoEstadoActual
            !==
            $nuevoEstado->codigo
        ) {

            throw ValidationException::withMessages([
                'estado_cita_id' =>
                    'Esta cita ya se encuentra en un estado final y no puede cambiarse desde esta acción.',
            ]);
        }


        /*
        * Si vuelve a ocupar horario, verificamos disponibilidad.
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

                $cita =
                    Cita::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $cita->id
                        );


                $estadoAnterior =
                    $cita
                        ->estado_cita_id;


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
                * NO_ASISTIO libera el horario y no representa
                * una atención clínica realizada.
                */
                if (
                    $nuevoEstado->codigo ===
                    'NO_ASISTIO'
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


                /*
                * El tratamiento inicia solamente cuando
                * realmente comienza una atención.
                */
                if (
                    $nuevoEstado->codigo ===
                    'EN_ATENCION'
                ) {

                    $this->iniciarTratamientoDeCita(
                        $cita
                    );
                }


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
                        ??
                        (
                            $nuevoEstado->codigo
                            ===
                            'NO_ASISTIO'
                                ? 'El paciente no asistió a la cita'
                                : null
                        ),

                    'fecha_cambio' =>
                        now(),
                ]);
            }
        );


        return back()->with(
            'success',
            $nuevoEstado->codigo === 'NO_ASISTIO'
                ? 'La cita fue marcada como NO ASISTIÓ. El tratamiento se mantiene sin cambios.'
                : 'Estado de la cita actualizado correctamente.'
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

        AlcanceClinico::autorizarCita(
            request()->user(),
            $cita
        );

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


        DB::transaction(
            function () use (
                $cita,
                $datos,
                $estadoCancelada
            ) {

                $cita =
                    Cita::query()
                        ->with([
                            'estadoCita',
                        ])
                        ->lockForUpdate()
                        ->findOrFail(
                            $cita->id
                        );


                $codigoEstadoActual =
                    $cita
                        ->estadoCita
                        ?->codigo;


                if (
                    $codigoEstadoActual ===
                    'CANCELADA'
                ) {

                    throw ValidationException::withMessages([
                        'estado' =>
                            'La cita ya se encuentra cancelada.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | SOLO SE CANCELA ANTES DE INICIAR ATENCIÓN
                |--------------------------------------------------------------------------
                |
                | Si ya está EN_ATENCION, debe resolverse desde el flujo clínico.
                | Una cita ATENDIDA tampoco puede cancelarse después.
                |
                */

                if (
                    !in_array(
                        $codigoEstadoActual,
                        [
                            'PENDIENTE',
                            'CONFIRMADA',
                        ],
                        true
                    )
                ) {

                    throw ValidationException::withMessages([
                        'estado' =>
                            'Solo una cita PENDIENTE o CONFIRMADA puede cancelarse.',
                    ]);
                }


                $estadoAnterior =
                    $cita
                        ->estado_cita_id;


                /*
                * NO tocamos tratamiento_citas.
                *
                * Si pertenece a un tratamiento:
                * - el vínculo histórico queda guardado;
                * - el tratamiento sigue PLANIFICADO o EN_PROCESO;
                * - esta cita NO contará como sesión realizada.
                */
                $cita->update([
                    'estado_cita_id' =>
                        $estadoCancelada->id,

                    'fecha_cancelacion' =>
                        now(),

                    'motivo_cancelacion' =>
                        $datos[
                            'motivo_cancelacion'
                        ]
                        ?? null,

                    'fecha_hora_fin_real' =>
                        null,
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
                        ??
                        'Cita cancelada',

                    'fecha_cambio' =>
                        now(),
                ]);
            }
        );


        return back()->with(
            'success',
            'Cita cancelada correctamente. Si pertenece a un tratamiento, el tratamiento continúa sin cambios.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | regprogramar citas
    |--------------------------------------------------------------------------
    */
    
    public function reprogramar(
        ReprogramarCitaRequest $request,
        Cita $cita
    ): RedirectResponse {

        AlcanceClinico::autorizarCita(
            $request->user(),
            $cita
        );

        $datos =
            $request->validated();


        /*
        |--------------------------------------------------------------------------
        | 1. VALIDAR ESTADO ACTUAL
        |--------------------------------------------------------------------------
        |
        | Solo permitimos reprogramar una cita que todavía no ha empezado.
        |
        */

        $codigoEstadoActual =
            $cita
                ->estadoCita()
                ->value('codigo');


        if (
            !in_array(
                $codigoEstadoActual,
                [
                    'PENDIENTE',
                    'CONFIRMADA',
                ],
                true
            )
        ) {

            throw ValidationException::withMessages([

                'estado' =>
                    'Solo una cita PENDIENTE o CONFIRMADA puede ser reprogramada.',

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | 2. OBTENER SERVICIO ORIGINAL
        |--------------------------------------------------------------------------
        */

        $servicioCita =
            $cita
                ->serviciosCita()
                ->with('servicio')
                ->first();


        if (
            !$servicioCita
            ||
            !$servicioCita->servicio
        ) {

            throw ValidationException::withMessages([

                'servicio' =>
                    'La cita no tiene un servicio asociado.',

            ]);
        }


        $servicio =
            $servicioCita->servicio;


        /*
        |--------------------------------------------------------------------------
        | 3. CALCULAR NUEVO HORARIO
        |--------------------------------------------------------------------------
        */

        $inicio =
            Carbon::parse(
                $datos['fecha_hora_inicio']
            );


        $fin =
            $inicio
                ->copy()
                ->addMinutes(
                    (int)
                    $servicio
                        ->duracion_estimada_minutos
                );


        /*
        |--------------------------------------------------------------------------
        | 4. VALIDAR DISPONIBILIDAD
        |--------------------------------------------------------------------------
        */

        $this->validarDisponibilidad(
            [
                'profesional_id' =>
                    (int)
                    $datos['profesional_id'],

                'consultorio_id' =>
                    $datos['consultorio_id']
                    ?? null,

                'fecha_hora_inicio' =>
                    $inicio,

                'fecha_hora_fin' =>
                    $fin,
            ],
            $cita
        );


        /*
        |--------------------------------------------------------------------------
        | 5. OBTENER ESTADOS
        |--------------------------------------------------------------------------
        */

        $estadoReprogramada =
            EstadoCita::query()
                ->where(
                    'codigo',
                    'REPROGRAMADA'
                )
                ->first();


        $estadoPendiente =
            EstadoCita::query()
                ->where(
                    'codigo',
                    'PENDIENTE'
                )
                ->first();


        if (
            !$estadoReprogramada
        ) {

            throw ValidationException::withMessages([

                'estado' =>
                    'No existe el estado REPROGRAMADA.',

            ]);
        }


        if (
            !$estadoPendiente
        ) {

            throw ValidationException::withMessages([

                'estado' =>
                    'No existe el estado PENDIENTE.',

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | 6. EJECUTAR REPROGRAMACIÓN
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $cita,
                $datos,
                $inicio,
                $fin,
                $servicioCita,
                $estadoReprogramada,
                $estadoPendiente
            ) {

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR LA CITA ORIGINAL
                |--------------------------------------------------------------------------
                */

                $citaBloqueada =
                    Cita::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $cita->id
                        );


                /*
                |--------------------------------------------------------------------------
                | BUSCAR SESIÓN DEL TRATAMIENTO
                |--------------------------------------------------------------------------
                |
                | Si existe, NO crearemos otra sesión.
                | La misma sesión se transferirá a la nueva cita.
                |
                */

                $tratamientoCita =
                    TratamientoCita::query()
                        ->where(
                            'cita_id',
                            $citaBloqueada->id
                        )
                        ->lockForUpdate()
                        ->first();


                $estadoAnteriorId =
                    $citaBloqueada
                        ->estado_cita_id;


                /*
                |--------------------------------------------------------------------------
                | MARCAR CITA ORIGINAL COMO REPROGRAMADA
                |--------------------------------------------------------------------------
                */

                $citaBloqueada->update([

                    'estado_cita_id' =>
                        $estadoReprogramada->id,

                    'fecha_hora_fin_real' =>
                        null,

                ]);


                /*
                |--------------------------------------------------------------------------
                | HISTORIAL DE LA CITA ORIGINAL
                |--------------------------------------------------------------------------
                */

                HistorialEstadoCita::create([

                    'cita_id' =>
                        $citaBloqueada->id,

                    'estado_anterior_id' =>
                        $estadoAnteriorId,

                    'estado_nuevo_id' =>
                        $estadoReprogramada->id,

                    'usuario_id' =>
                        Auth::id(),

                    'motivo' =>
                        $datos['motivo']
                        ?:
                        'Cita reprogramada',

                    'fecha_cambio' =>
                        now(),

                ]);


                /*
                |--------------------------------------------------------------------------
                | CREAR NUEVA CITA
                |--------------------------------------------------------------------------
                */

                $nuevaCita =
                    Cita::create([

                        'cita_origen_id' =>
                            $citaBloqueada->id,

                        'paciente_id' =>
                            $citaBloqueada->paciente_id,

                        'profesional_id' =>
                            (int)
                            $datos['profesional_id'],

                        'consultorio_id' =>
                            $datos['consultorio_id']
                            ?? null,

                        'estado_cita_id' =>
                            $estadoPendiente->id,

                        'fecha_hora_inicio' =>
                            $inicio,

                        'fecha_hora_fin' =>
                            $fin,

                        'fecha_hora_fin_real' =>
                            null,

                        'motivo' =>
                            $citaBloqueada->motivo,

                        'observaciones' =>
                            $citaBloqueada->observaciones,

                        'fecha_cancelacion' =>
                            null,

                        'motivo_cancelacion' =>
                            null,

                        'usuario_creador_id' =>
                            Auth::id(),

                    ]);


                /*
                |--------------------------------------------------------------------------
                | COPIAR SERVICIO ORIGINAL
                |--------------------------------------------------------------------------
                |
                | Se conserva el snapshot histórico del servicio de esa sesión.
                |
                */

                $nuevaCita
                    ->serviciosCita()
                    ->create([

                        'servicio_id' =>
                            $servicioCita
                                ->servicio_id,

                        'cantidad' =>
                            $servicioCita
                                ->cantidad
                            ??
                            1,

                        'precio_unitario' =>
                            $servicioCita
                                ->precio_unitario,

                        'descuento' =>
                            $servicioCita
                                ->descuento
                            ??
                            0,

                        'total' =>
                            $servicioCita
                                ->total,

                        'estado' =>
                            'PENDIENTE',

                        'observaciones' =>
                            $servicioCita
                                ->observaciones,

                    ]);


                /*
                |--------------------------------------------------------------------------
                | TRANSFERIR SESIÓN DEL TRATAMIENTO
                |--------------------------------------------------------------------------
                |
                | EJEMPLO:
                |
                | Antes:
                | tratamiento #8 -> cita #20 -> sesión 2
                |
                | Después:
                | tratamiento #8 -> cita #35 -> sesión 2
                |
                | Reprogramar NO significa crear una nueva sesión.
                |
                */

                if (
                    $tratamientoCita
                ) {

                    /*
                    * Compatibilidad financiera:
                    *
                    * Si todavía existen pagos antiguos asociados solamente
                    * a la cita original, los enlazamos también al tratamiento
                    * antes de mover la sesión.
                    *
                    * Conservamos cita_id porque indica dónde se registró
                    * originalmente el pago.
                    */

                    DB::table('pagos')
                        ->where(
                            'cita_id',
                            $citaBloqueada->id
                        )
                        ->whereNull(
                            'tratamiento_paciente_id'
                        )
                        ->update([

                            'tratamiento_paciente_id' =>
                                $tratamientoCita
                                    ->tratamiento_paciente_id,

                            'updated_at' =>
                                now(),

                        ]);


                    /*
                    * La MISMA fila de tratamiento_citas cambia
                    * de la cita vieja a la nueva.
                    *
                    * numero_sesion se conserva.
                    */

                    $tratamientoCita->update([

                        'cita_id' =>
                            $nuevaCita->id,

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | HISTORIAL INICIAL DE LA NUEVA CITA
                |--------------------------------------------------------------------------
                */

                HistorialEstadoCita::create([

                    'cita_id' =>
                        $nuevaCita->id,

                    'estado_anterior_id' =>
                        null,

                    'estado_nuevo_id' =>
                        $estadoPendiente->id,

                    'usuario_id' =>
                        Auth::id(),

                    'motivo' =>
                        'Cita creada por reprogramación de la cita #'
                        .
                        $citaBloqueada->id,

                    'fecha_cambio' =>
                        now(),

                ]);
            }
        );


        return back()->with(
            'success',
            'La cita fue reprogramada correctamente.'
        );
    }


    public function agendaSemana(
        Request $request
    ): JsonResponse {

        $profesionalId = AlcanceClinico::profesionalId(
            $request->user()
        );

        if ($profesionalId !== null) {
            $request->merge([
                'profesional_id' => $profesionalId,
            ]);
        }

        $datos = $request->validate([

            'fecha' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'profesional_id' => [
                'nullable',
                'integer',
                'exists:profesionales,id',
            ],

        ]);


        $zonaHoraria = config(
            'app.timezone',
            'America/Lima'
        );


        /*
        |--------------------------------------------------------------------------
        | Fecha base
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $datos['fecha']
            )
        ) {

            $fechaBase =
                Carbon::createFromFormat(
                    'Y-m-d',
                    $datos['fecha'],
                    $zonaHoraria
                );

        } else {

            $fechaBase =
                now(
                    $zonaHoraria
                );

        }


        /*
        |--------------------------------------------------------------------------
        | Inicio y fin de semana
        |--------------------------------------------------------------------------
        */

        $inicioSemana =
            $fechaBase
                ->copy()
                ->startOfWeek(
                    Carbon::MONDAY
                )
                ->startOfDay();


        $finSemana =
            $fechaBase
                ->copy()
                ->endOfWeek(
                    Carbon::SUNDAY
                )
                ->endOfDay();


        /*
        |--------------------------------------------------------------------------
        | Citas
        |--------------------------------------------------------------------------
        */

        $citas = AlcanceClinico::citas(
            Cita::query(),
            $request->user()
        )

            ->with([

                'paciente:id,codigo,nombres,apellidos,numero_documento,telefono',

                'profesional:id,nombres,apellidos,numero_colegiatura',

                'consultorio:id,codigo,nombre',

                'estadoCita:id,codigo,nombre,es_final',

                'serviciosCita.servicio:id,codigo,nombre,duracion_estimada_minutos,precio_actual',

                'tratamientoCita.tratamientoPaciente.servicio',

            ])

            ->whereBetween(
                'fecha_hora_inicio',
                [
                    $inicioSemana,
                    $finSemana,
                ]
            )

            /*
            |--------------------------------------------------------------------------
            | Estados visibles en agenda
            |--------------------------------------------------------------------------
            */

            ->whereHas(
                'estadoCita',

                function ($query) {

                    $query->whereIn(
                        'codigo',
                        [
                            'PENDIENTE',
                            'CONFIRMADA',
                            'EN_ATENCION',
                            'ATENDIDA',
                        ]
                    );

                }
            )

            /*
            |--------------------------------------------------------------------------
            | Profesional opcional
            |--------------------------------------------------------------------------
            */

            ->when(
                !empty(
                    $datos['profesional_id']
                ),

                function ($query) use (
                    $datos
                ) {

                    $query->where(
                        'profesional_id',
                        $datos['profesional_id']
                    );

                }
            )

            ->orderBy(
                'fecha_hora_inicio'
            )

            ->get();


        /*
        |--------------------------------------------------------------------------
        | Preparar citas para Vue
        |--------------------------------------------------------------------------
        */

        $citas->each(
            function (Cita $cita) use (
                $zonaHoraria
            ) {

                $inicio =
                    $cita
                        ->fecha_hora_inicio
                        ->copy()
                        ->timezone(
                            $zonaHoraria
                        );


                $finBase =
                    $cita->fecha_hora_fin_real
                    ??
                    $cita->fecha_hora_fin;


                $fin =
                    $finBase
                        ?->copy()
                        ->timezone(
                            $zonaHoraria
                        );


                $cita->setAttribute(
                    'agenda_fecha',
                    $inicio->format(
                        'Y-m-d'
                    )
                );


                $cita->setAttribute(
                    'agenda_inicio_texto',
                    $inicio->format(
                        'H:i'
                    )
                );


                $cita->setAttribute(
                    'agenda_fin_texto',

                    $fin
                        ? $fin->format(
                            'H:i'
                        )
                        : null
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Crear los 7 días
        |--------------------------------------------------------------------------
        */

        $dias = [];


        for (
            $i = 0;
            $i < 7;
            $i++
        ) {

            $dia =
                $inicioSemana
                    ->copy()
                    ->addDays(
                        $i
                    );


            $dias[] = [

                'fecha' =>
                    $dia->format(
                        'Y-m-d'
                    ),

                'dia' =>
                    $dia->format(
                        'd'
                    ),

                'mes' =>
                    $dia->format(
                        'm'
                    ),

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Respuesta
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'inicio_semana' =>
                $inicioSemana->format(
                    'Y-m-d'
                ),

            'fin_semana' =>
                $finSemana->format(
                    'Y-m-d'
                ),

            'dias' =>
                $dias,

            'citas' =>
                $citas,

        ]);
    }

    private function prepararTratamiento(
        array $datos
    ): array {

        /*
        |--------------------------------------------------------------------------
        | CONTINUAR TRATAMIENTO EXISTENTE
        |--------------------------------------------------------------------------
        */

        if (
            $datos['tipo_atencion']
            ===
            'CONTINUAR_TRATAMIENTO'
        ) {

            $tratamiento =
                TratamientoPaciente::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $datos['tratamiento_paciente_id']
                    );


            /*
            * Protección:
            * el tratamiento debe pertenecer al mismo paciente.
            */

            if (
                (int) $tratamiento->paciente_id
                !==
                (int) $datos['paciente_id']
            ) {

                throw ValidationException::withMessages([

                    'tratamiento_paciente_id' =>
                        'El tratamiento seleccionado no pertenece al paciente.',

                ]);
            }


            /*
            * Solo se pueden continuar tratamientos activos.
            */

            if (
                !in_array(
                    $tratamiento->estado,
                    [
                        TratamientoPaciente::ESTADO_PLANIFICADO,
                        TratamientoPaciente::ESTADO_EN_PROCESO,
                    ],
                    true
                )
            ) {

                throw ValidationException::withMessages([

                    'tratamiento_paciente_id' =>
                        'Este tratamiento ya no se encuentra activo.',

                ]);
            }


            /*
            * El servicio de la cita debe corresponder
            * al servicio del tratamiento.
            */

            if (
                (int) $tratamiento->servicio_id
                !==
                (int) $datos['servicio_id']
            ) {

                throw ValidationException::withMessages([

                    'servicio_id' =>
                        'El servicio seleccionado no corresponde al tratamiento.',

                ]);
            }


            $ultimaSesion =
                (int)
                $tratamiento
                    ->tratamientoCitas()
                    ->max('numero_sesion');


            return [

                'tratamiento' =>
                    $tratamiento,

                'numero_sesion' =>
                    $ultimaSesion + 1,

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | NUEVO TRATAMIENTO
        |--------------------------------------------------------------------------
        */

        $tratamiento =
            TratamientoPaciente::create([

                'paciente_id' =>
                    $datos['paciente_id'],

                'servicio_id' =>
                    $datos['servicio_id'],

                'profesional_id' =>
                    $datos['profesional_id'],

                'precio_acordado' =>
                    $datos['precio_acordado'],

                /*
                * Todavía no inició clínicamente.
                * Solo tiene una cita programada.
                */
                'estado' =>
                    TratamientoPaciente::ESTADO_PLANIFICADO,

                'fecha_inicio' =>
                    null,

                'fecha_fin' =>
                    null,

                'observaciones' =>
                    null,

            ]);


        return [

            'tratamiento' =>
                $tratamiento,

            'numero_sesion' =>
                1,

        ];
    }

    public function finalizarAtencion(
        FinalizarAtencionRequest $request,
        Cita $cita
    ): RedirectResponse {

        DB::transaction(
            function () use (
                $request,
                $cita
            ) {

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR CITA
                |--------------------------------------------------------------------------
                */

                $cita =
                    Cita::query()
                        ->with([
                            'estadoCita',
                        ])
                        ->lockForUpdate()
                        ->findOrFail(
                            $cita->id
                        );

                AlcanceClinico::autorizarCita(
                    $request->user(),
                    $cita
                );


                /*
                |--------------------------------------------------------------------------
                | SOLO UNA CITA EN ATENCIÓN PUEDE FINALIZARSE
                |--------------------------------------------------------------------------
                */

                if (
                    $cita->estadoCita?->codigo
                    !==
                    'EN_ATENCION'
                ) {

                    throw ValidationException::withMessages([

                        'estado' =>
                            'Solo una cita que se encuentra EN ATENCIÓN puede finalizarse.',

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | ESTADO ATENDIDA
                |--------------------------------------------------------------------------
                */

                $estadoAtendidaId =
                    EstadoCita::query()
                        ->where(
                            'codigo',
                            'ATENDIDA'
                        )
                        ->value('id');


                if (
                    !$estadoAtendidaId
                ) {

                    throw ValidationException::withMessages([

                        'estado' =>
                            'No se encontró el estado ATENDIDA.',

                    ]);
                }


                $estadoAnteriorId =
                    $cita->estado_cita_id;


                /*
                |--------------------------------------------------------------------------
                | BUSCAR RELACIÓN CON TRATAMIENTO
                |--------------------------------------------------------------------------
                */

                $relacionTratamiento =
                    TratamientoCita::query()
                        ->where(
                            'cita_id',
                            $cita->id
                        )
                        ->lockForUpdate()
                        ->first();


                /*
                |--------------------------------------------------------------------------
                | SI LA CITA PERTENECE A UN TRATAMIENTO
                |--------------------------------------------------------------------------
                */

                $tratamiento =
                    null;


                if (
                    $relacionTratamiento
                ) {

                    $tratamiento =
                        TratamientoPaciente::query()
                            ->with([
                                'servicio',
                            ])
                            ->lockForUpdate()
                            ->findOrFail(
                                $relacionTratamiento
                                    ->tratamiento_paciente_id
                            );

                    AlcanceClinico::autorizarTratamiento(
                        $request->user(),
                        $tratamiento
                    );


                    /*
                    * No se puede atender un tratamiento
                    * ya cerrado.
                    */

                    if (
                        in_array(
                            $tratamiento->estado,
                            [
                                TratamientoPaciente::ESTADO_COMPLETADO,
                                TratamientoPaciente::ESTADO_CANCELADO,
                            ],
                            true
                        )
                    ) {

                        throw ValidationException::withMessages([

                            'accion_tratamiento' =>
                                'El tratamiento ya se encuentra cerrado.',

                        ]);
                    }


                    /*
                    * Una cita ligada a tratamiento
                    * necesita indicar qué pasará después.
                    */

                    if (
                        empty(
                            $request->accion_tratamiento
                        )
                    ) {

                        throw ValidationException::withMessages([

                            'accion_tratamiento' =>
                                'Indica qué sucederá con el tratamiento.',

                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDAR CITAS FUTURAS DEL TRATAMIENTO
                |--------------------------------------------------------------------------
                */

                $tieneCitaFuturaActiva =
                    false;


                if (
                    $tratamiento
                ) {

                    $tieneCitaFuturaActiva =
                        TratamientoCita::query()

                            ->where(
                                'tratamiento_paciente_id',
                                $tratamiento->id
                            )

                            ->where(
                                'cita_id',
                                '!=',
                                $cita->id
                            )

                            ->whereHas(
                                'cita.estadoCita',

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

                            ->exists();
                }


                /*
                |--------------------------------------------------------------------------
                | PROGRAMAR SIGUIENTE SESIÓN
                |--------------------------------------------------------------------------
                */

                $nuevaCita =
                    null;


                if (
                    $tratamiento
                    &&
                    $request->accion_tratamiento
                    ===
                    'PROGRAMAR_SIGUIENTE'
                ) {

                    /*
                    * Evitamos generar dos próximas citas.
                    */

                    if (
                        $tieneCitaFuturaActiva
                    ) {

                        throw ValidationException::withMessages([

                            'accion_tratamiento' =>
                                'Este tratamiento ya tiene una próxima cita programada.',

                        ]);
                    }


                    $servicio =
                        $tratamiento->servicio;


                    if (
                        !$servicio
                    ) {

                        throw ValidationException::withMessages([

                            'accion_tratamiento' =>
                                'El tratamiento no tiene un servicio asociado.',

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | NÚMERO DE SIGUIENTE SESIÓN
                    |--------------------------------------------------------------------------
                    */

                    $ultimaSesion =
                        (int)
                        TratamientoCita::query()

                            ->where(
                                'tratamiento_paciente_id',
                                $tratamiento->id
                            )

                            ->max(
                                'numero_sesion'
                            );


                    $numeroSesion =
                        $ultimaSesion + 1;


                    /*
                    |--------------------------------------------------------------------------
                    | HORARIO
                    |--------------------------------------------------------------------------
                    */

                    $inicioSiguiente =
                        Carbon::parse(
                            $request
                                ->fecha_hora_siguiente
                        );


                    $finSiguiente =
                        $inicioSiguiente
                            ->copy()
                            ->addMinutes(
                                (int)
                                $servicio
                                    ->duracion_estimada_minutos
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | ESTADO PENDIENTE
                    |--------------------------------------------------------------------------
                    */

                    $estadoPendienteId =
                        EstadoCita::query()

                            ->where(
                                'codigo',
                                'PENDIENTE'
                            )

                            ->value('id');


                    if (
                        !$estadoPendienteId
                    ) {

                        throw ValidationException::withMessages([

                            'estado' =>
                                'No se encontró el estado PENDIENTE.',

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | DATOS PARA VALIDAR DISPONIBILIDAD
                    |--------------------------------------------------------------------------
                    */

                    $datosDisponibilidad = [

                        'profesional_id' =>
                            (int)
                            $request
                                ->profesional_id,

                        'consultorio_id' =>
                            $request
                                ->consultorio_id
                                ?: null,

                        'estado_cita_id' =>
                            $estadoPendienteId,

                        'fecha_hora_inicio' =>
                            $inicioSiguiente,

                        'fecha_hora_fin' =>
                            $finSiguiente,

                    ];


                    /*
                    * Usamos la misma validación
                    * que ya utiliza Nueva cita.
                    */

                    $this->validarDisponibilidad(
                        $datosDisponibilidad
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | CREAR PRÓXIMA CITA
                    |--------------------------------------------------------------------------
                    */

                    $nuevaCita =
                        Cita::create([

                            'paciente_id' =>
                                $tratamiento
                                    ->paciente_id,

                            'profesional_id' =>
                                (int)
                                $request
                                    ->profesional_id,

                            'consultorio_id' =>
                                $request
                                    ->consultorio_id
                                    ?: null,

                            'estado_cita_id' =>
                                $estadoPendienteId,

                            'fecha_hora_inicio' =>
                                $inicioSiguiente,

                            'fecha_hora_fin' =>
                                $finSiguiente,

                            'motivo' =>
                                $request
                                    ->motivo_siguiente
                                ?:
                                (
                                    $servicio->nombre
                                    .
                                    ' - sesión '
                                    .
                                    $numeroSesion
                                ),

                            'observaciones' =>
                                $request
                                    ->observaciones_siguiente,

                            'usuario_creador_id' =>
                                Auth::id(),

                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | SERVICIO DE LA SIGUIENTE CITA
                    |--------------------------------------------------------------------------
                    */

                    $this->guardarServicioCita(
                        $nuevaCita,
                        $servicio
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | RELACIONAR SIGUIENTE SESIÓN
                    |--------------------------------------------------------------------------
                    */

                    TratamientoCita::create([

                        'tratamiento_paciente_id' =>
                            $tratamiento->id,

                        'cita_id' =>
                            $nuevaCita->id,

                        'numero_sesion' =>
                            $numeroSesion,

                        'observaciones' =>
                            'Sesión programada al finalizar la cita anterior.',

                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | HISTORIAL DE NUEVA CITA
                    |--------------------------------------------------------------------------
                    */

                    HistorialEstadoCita::create([

                        'cita_id' =>
                            $nuevaCita->id,

                        'estado_anterior_id' =>
                            null,

                        'estado_nuevo_id' =>
                            $estadoPendienteId,

                        'usuario_id' =>
                            Auth::id(),

                        'motivo' =>
                            'Próxima sesión del tratamiento programada.',

                        'fecha_cambio' =>
                            now(),

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR TRATAMIENTO
                |--------------------------------------------------------------------------
                */

                if (
                    $tratamiento
                ) {

                    /*
                    * Si todavía estaba PLANIFICADO,
                    * significa que clínicamente ya comenzó.
                    */

                    if (
                        $tratamiento->estado
                        ===
                        TratamientoPaciente::ESTADO_PLANIFICADO
                    ) {

                        $tratamiento->fecha_inicio =
                            $tratamiento->fecha_inicio
                            ?:
                            now()->toDateString();
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | COMPLETAR
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $request->accion_tratamiento
                        ===
                        'COMPLETAR'
                    ) {

                        /*
                        * No permitimos cerrar el tratamiento
                        * dejando otra cita activa.
                        */

                        if (
                            $tieneCitaFuturaActiva
                        ) {

                            throw ValidationException::withMessages([

                                'accion_tratamiento' =>
                                    'No puedes completar el tratamiento porque todavía tiene una cita futura activa.',

                            ]);
                        }


                        $tratamiento->estado =
                            TratamientoPaciente::ESTADO_COMPLETADO;


                        $tratamiento->fecha_fin =
                            now()
                                ->toDateString();

                    } else {

                        /*
                        * Tanto PROGRAMAR_SIGUIENTE como
                        * CONTINUAR_SIN_FECHA mantienen
                        * el tratamiento abierto.
                        */

                        $tratamiento->estado =
                            TratamientoPaciente::ESTADO_EN_PROCESO;


                        $tratamiento->fecha_fin =
                            null;
                    }


                    $tratamiento->save();
                }


                /*
                |--------------------------------------------------------------------------
                | FINALIZAR CITA ACTUAL
                |--------------------------------------------------------------------------
                */

                $cita->estado_cita_id =
                    $estadoAtendidaId;


                $cita->fecha_hora_fin_real =
                    now();


                $cita->save();


                /*
                |--------------------------------------------------------------------------
                | HISTORIAL DE LA CITA ACTUAL
                |--------------------------------------------------------------------------
                */

                HistorialEstadoCita::create([

                    'cita_id' =>
                        $cita->id,

                    'estado_anterior_id' =>
                        $estadoAnteriorId,

                    'estado_nuevo_id' =>
                        $estadoAtendidaId,

                    'usuario_id' =>
                        Auth::id(),

                    'motivo' =>
                        'Atención finalizada.',

                    'fecha_cambio' =>
                        now(),

                ]);
            }
        );


        return back()->with(
            'success',
            'Atención finalizada correctamente.'
        );
    }

    private function iniciarTratamientoDeCita(
        Cita $cita
    ): void {

        $relacion =
            TratamientoCita::query()
                ->where(
                    'cita_id',
                    $cita->id
                )
                ->first();


        /*
        * Cita normal, sin tratamiento.
        */
        if (
            !$relacion
        ) {
            return;
        }


        $tratamiento =
            TratamientoPaciente::query()
                ->lockForUpdate()
                ->find(
                    $relacion
                        ->tratamiento_paciente_id
                );


        if (
            !$tratamiento
        ) {
            return;
        }


        /*
        * Solo iniciamos un tratamiento
        * que todavía estaba PLANIFICADO.
        */
        if (
            $tratamiento->estado
            ===
            TratamientoPaciente::ESTADO_PLANIFICADO
        ) {

            $tratamiento->estado =
                TratamientoPaciente::ESTADO_EN_PROCESO;


            $tratamiento->fecha_inicio =
                $tratamiento->fecha_inicio
                ?:
                now()->toDateString();


            $tratamiento->save();
        }
    }
}
