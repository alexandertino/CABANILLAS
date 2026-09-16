<script setup>

import {
    computed,
    ref,
    watch,
} from 'vue';

import {
    useForm,
} from '@inertiajs/vue3';

import {
    CalendarClock,
    X,
    LoaderCircle,
    Clock3,
    Stethoscope,
    DoorOpen,
    CalendarDays,
    RotateCcw,
    UserRound,
    Timer,
} from 'lucide-vue-next';


/* =========================================================
   PROPS
========================================================= */

const props = defineProps({

    open: {
        type: Boolean,
        default: false,
    },

    cita: {
        type: Object,
        default: null,
    },

    profesionales: {
        type: Array,
        default: () => [],
    },

    consultorios: {
        type: Array,
        default: () => [],
    },

});


/* =========================================================
   EMITS
========================================================= */

const emit = defineEmits([
    'close',
]);


/* =========================================================
   FORMULARIO
========================================================= */

const form = useForm({

    profesional_id: '',

    consultorio_id: '',

    fecha_hora_inicio: '',

    motivo: '',

});


/* =========================================================
   DISPONIBILIDAD
========================================================= */

const horariosDisponibles =
    ref([]);

const cargandoHorarios =
    ref(false);

const errorHorarios =
    ref('');

let controladorHorarios =
    null;

let temporizadorHorarios =
    null;


/* =========================================================
   SERVICIO DE LA CITA
========================================================= */

const servicioCita =
    computed(() => {

        return (
            props.cita
                ?.servicios_cita
                ?.[0]
            ??
            null
        );

    });


const servicio =
    computed(() => {

        return (
            servicioCita.value
                ?.servicio
            ??
            null
        );

    });


const servicioId =
    computed(() => {

        return (
            servicioCita.value
                ?.servicio_id
            ??
            servicio.value
                ?.id
            ??
            ''
        );

    });


const duracionServicio =
    computed(() => {

        return Number(
            servicio.value
                ?.duracion_estimada_minutos
            ??
            0
        );

    });


/* =========================================================
   PACIENTE
========================================================= */

const nombrePaciente =
    computed(() => {

        if (
            !props.cita?.paciente
        ) {

            return 'Paciente';
        }


        return [
            props.cita.paciente.nombres,
            props.cita.paciente.apellidos,
        ]
            .filter(Boolean)
            .join(' ');

    });


/* =========================================================
   PROFESIONAL ACTUAL
========================================================= */

const nombreProfesionalActual =
    computed(() => {

        if (
            !props.cita?.profesional
        ) {

            return '—';
        }


        return [
            props.cita.profesional.nombres,
            props.cita.profesional.apellidos,
        ]
            .filter(Boolean)
            .join(' ');

    });


/* =========================================================
   NORMALIZAR FECHA
========================================================= */

function normalizarFechaHora(
    valor
) {

    if (
        !valor
    ) {

        return '';
    }


    return String(
        valor
    )
        .replace(
            ' ',
            'T'
        )
        .slice(
            0,
            16
        );
}


/* =========================================================
   FORMATEAR FECHA ORIGINAL
========================================================= */

function formatoFechaHora(
    valor
) {

    const normalizada =
        normalizarFechaHora(
            valor
        );


    if (
        !normalizada
    ) {

        return '—';
    }


    const coincidencia =
        normalizada.match(
            /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/
        );


    if (
        !coincidencia
    ) {

        return valor;
    }


    const [
        ,
        anio,
        mes,
        dia,
        hora,
        minuto,
    ] = coincidencia;


    const fecha =
        new Date(
            Number(anio),
            Number(mes) - 1,
            Number(dia),
            Number(hora),
            Number(minuto)
        );


    return new Intl.DateTimeFormat(
        'es-PE',
        {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }
    ).format(
        fecha
    );
}


/* =========================================================
   FECHA SELECCIONADA
========================================================= */

function fechaSeleccionada() {

    if (
        !form.fecha_hora_inicio
    ) {

        return '';
    }


    const fecha =
        String(
            form.fecha_hora_inicio
        ).slice(
            0,
            10
        );


    if (
        !/^\d{4}-\d{2}-\d{2}$/.test(
            fecha
        )
    ) {

        return '';
    }


    return fecha;
}


/* =========================================================
   FECHA FINAL ESTIMADA
========================================================= */

const fechaHoraFin =
    computed(() => {

        if (
            !form.fecha_hora_inicio
            ||
            !duracionServicio.value
        ) {

            return '';
        }


        const coincidencia =
            String(
                form.fecha_hora_inicio
            ).match(
                /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/
            );


        if (
            !coincidencia
        ) {

            return '';
        }


        const [
            ,
            anio,
            mes,
            dia,
            hora,
            minuto,
        ] = coincidencia;


        const fecha =
            new Date(
                Number(anio),
                Number(mes) - 1,
                Number(dia),
                Number(hora),
                Number(minuto),
                0,
                0
            );


        fecha.setMinutes(
            fecha.getMinutes()
            +
            duracionServicio.value
        );


        const y =
            fecha.getFullYear();


        const m =
            String(
                fecha.getMonth() + 1
            ).padStart(
                2,
                '0'
            );


        const d =
            String(
                fecha.getDate()
            ).padStart(
                2,
                '0'
            );


        const h =
            String(
                fecha.getHours()
            ).padStart(
                2,
                '0'
            );


        const min =
            String(
                fecha.getMinutes()
            ).padStart(
                2,
                '0'
            );


        return (
            `${y}-${m}-${d}T${h}:${min}`
        );

    });


/* =========================================================
   HORA FINAL
========================================================= */

const horaFinal =
    computed(() => {

        if (
            !fechaHoraFin.value
        ) {

            return '--:--';
        }


        return (
            fechaHoraFin.value
                .split('T')[1]
            ??
            '--:--'
        );

    });


/* =========================================================
   FORMATO DURACIÓN
========================================================= */

function formatoDuracion(
    minutos
) {

    const valor =
        Number(
            minutos
            ??
            0
        );


    if (
        valor <= 0
    ) {

        return 'Sin duración';
    }


    if (
        valor < 60
    ) {

        return `${valor} min`;
    }


    const horas =
        Math.floor(
            valor / 60
        );


    const resto =
        valor % 60;


    if (
        resto === 0
    ) {

        return `${horas} h`;
    }


    return (
        `${horas} h ${resto} min`
    );
}


/* =========================================================
   CARGAR FORMULARIO
========================================================= */

function cargarFormulario() {

    form.clearErrors();


    horariosDisponibles.value =
        [];


    errorHorarios.value =
        '';


    if (
        !props.cita
    ) {

        form.reset();

        return;
    }


    form.profesional_id =
        props.cita.profesional_id
        ??
        props.cita.profesional?.id
        ??
        '';


    form.consultorio_id =
        props.cita.consultorio_id
        ??
        props.cita.consultorio?.id
        ??
        '';


    /*
     * Dejamos la fecha vacía a propósito.
     *
     * La reprogramación debe elegir
     * una NUEVA fecha/hora.
     */

    form.fecha_hora_inicio =
        '';


    form.motivo =
        '';

}


/* =========================================================
   CARGAR HORARIOS DISPONIBLES
========================================================= */

async function cargarHorariosDisponibles() {

    const fecha =
        fechaSeleccionada();


    if (
        !props.cita?.id
        ||
        !fecha
        ||
        !form.profesional_id
        ||
        !servicioId.value
    ) {

        horariosDisponibles.value =
            [];


        errorHorarios.value =
            '';


        return;
    }


    if (
        typeof window
        ===
        'undefined'
    ) {

        return;
    }


    controladorHorarios?.abort();


    controladorHorarios =
        new AbortController();


    cargandoHorarios.value =
        true;


    errorHorarios.value =
        '';


    try {

        const parametros =
            new URLSearchParams();


        parametros.set(
            'fecha',
            fecha
        );


        parametros.set(
            'profesional_id',
            form.profesional_id
        );


        parametros.set(
            'servicio_id',
            servicioId.value
        );


        /*
         * Esta cita será sustituida por
         * la nueva, por eso se excluye
         * al comprobar conflictos.
         */

        parametros.set(
            'cita_id',
            props.cita.id
        );


        if (
            form.consultorio_id
        ) {

            parametros.set(
                'consultorio_id',
                form.consultorio_id
            );
        }


        const respuesta =
            await fetch(
                `/clinica/citas/disponibilidad?${parametros.toString()}`,
                {
                    signal:
                        controladorHorarios.signal,

                    headers: {
                        Accept:
                            'application/json',
                    },
                }
            );


        if (
            !respuesta.ok
        ) {

            const texto =
                await respuesta.text();


            console.error(
                'Respuesta disponibilidad reprogramación:',
                texto
            );


            throw new Error(
                `HTTP ${respuesta.status}`
            );
        }


        const datos =
            await respuesta.json();


        horariosDisponibles.value =
            datos.horarios
            ??
            [];

    }
    catch (
        excepcion
    ) {

        if (
            excepcion.name
            ===
            'AbortError'
        ) {

            return;
        }


        console.error(
            'Error consultando horarios:',
            excepcion
        );


        horariosDisponibles.value =
            [];


        errorHorarios.value =
            'No se pudieron consultar los horarios disponibles.';

    }
    finally {

        cargandoHorarios.value =
            false;
    }
}


/* =========================================================
   SELECCIONAR HORARIO
========================================================= */

function seleccionarHorario(
    horario
) {

    form.fecha_hora_inicio =
        horario.inicio;


    form.clearErrors(
        'fecha_hora_inicio'
    );
}


/* =========================================================
   SABER SI HORARIO ESTÁ SELECCIONADO
========================================================= */

function horarioSeleccionado(
    horario
) {

    return (
        normalizarFechaHora(
            form.fecha_hora_inicio
        )
        ===
        normalizarFechaHora(
            horario.inicio
        )
    );
}


/* =========================================================
   GUARDAR REPROGRAMACIÓN
========================================================= */

function reprogramar() {

    form.clearErrors();


    let hayError =
        false;


    if (
        !form.profesional_id
    ) {

        form.setError(
            'profesional_id',
            'Selecciona un profesional.'
        );


        hayError =
            true;
    }


    if (
        !form.fecha_hora_inicio
    ) {

        form.setError(
            'fecha_hora_inicio',
            'Selecciona la nueva fecha y hora.'
        );


        hayError =
            true;
    }


    if (
        hayError
    ) {

        return;
    }


    form.post(
        `/clinica/citas/${props.cita.id}/reprogramar`,
        {
            preserveScroll:
                true,

            onSuccess: () => {

                form.reset();


                horariosDisponibles.value =
                    [];


                emit(
                    'close'
                );

            },

            onError: errores => {

                console.error(
                    'Errores al reprogramar cita:',
                    errores
                );

            },
        }
    );
}


/* =========================================================
   CERRAR
========================================================= */

function cerrar() {

    if (
        form.processing
    ) {

        return;
    }


    controladorHorarios?.abort();


    emit(
        'close'
    );
}


/* =========================================================
   WATCH - MODAL
========================================================= */

watch(
    [
        () =>
            props.open,

        () =>
            props.cita,
    ],

    ([abierto]) => {

        if (
            abierto
        ) {

            cargarFormulario();
        }

    },

    {
        immediate:
            true,
    }
);


/* =========================================================
   WATCH - DISPONIBILIDAD
========================================================= */

watch(
    () => [
        form.profesional_id,
        form.consultorio_id,
        fechaSeleccionada(),
        servicioId.value,
    ],

    () => {

        if (
            !props.open
        ) {

            return;
        }


        clearTimeout(
            temporizadorHorarios
        );


        temporizadorHorarios =
            setTimeout(
                cargarHorariosDisponibles,
                250
            );

    }
);

</script>


<template>

    <div
        v-if="open && cita"
        class="
            fixed
            inset-0
            z-[120]
            flex
            items-center
            justify-center
            bg-slate-950/40
            p-4
            backdrop-blur-[2px]
        "
        @click.self="cerrar"
    >

        <section
            class="
                flex
                max-h-[92vh]
                w-full
                max-w-2xl
                flex-col
                overflow-hidden
                rounded-3xl
                bg-white
                shadow-2xl
            "
        >

            <!-- =================================================
                 HEADER
            ================================================== -->

            <header
                class="
                    flex
                    items-start
                    justify-between
                    border-b
                    border-slate-100
                    px-6
                    py-5
                "
            >

                <div
                    class="
                        flex
                        gap-3
                    "
                >

                    <div
                        class="
                            flex
                            h-11
                            w-11
                            shrink-0
                            items-center
                            justify-center
                            rounded-2xl
                            bg-violet-50
                            text-violet-600
                        "
                    >

                        <CalendarClock
                            :size="22"
                        />

                    </div>


                    <div>

                        <h2
                            class="
                                text-lg
                                font-bold
                                text-slate-900
                            "
                        >
                            Reprogramar cita
                        </h2>


                        <p
                            class="
                                mt-1
                                text-sm
                                text-slate-500
                            "
                        >
                            Selecciona una nueva fecha y horario disponible.
                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    class="
                        flex
                        h-9
                        w-9
                        items-center
                        justify-center
                        rounded-xl
                        text-slate-400
                        transition
                        hover:bg-slate-100
                        hover:text-slate-700
                    "
                    @click="cerrar"
                >

                    <X
                        :size="19"
                    />

                </button>

            </header>


            <!-- =================================================
                 CONTENIDO
            ================================================== -->

            <div
                class="
                    flex-1
                    space-y-6
                    overflow-y-auto
                    px-6
                    py-5
                "
            >

                <!-- =============================================
                     INFORMACIÓN ACTUAL
                ============================================== -->

                <div
                    class="
                        rounded-2xl
                        border
                        border-slate-200
                        bg-slate-50
                        p-4
                    "
                >

                    <p
                        class="
                            text-xs
                            font-bold
                            uppercase
                            tracking-wide
                            text-slate-400
                        "
                    >
                        Cita actual
                    </p>


                    <div
                        class="
                            mt-4
                            grid
                            gap-4
                            sm:grid-cols-2
                        "
                    >

                        <!-- PACIENTE -->

                        <div
                            class="
                                flex
                                gap-2
                            "
                        >

                            <UserRound
                                :size="17"
                                class="
                                    mt-0.5
                                    shrink-0
                                    text-slate-400
                                "
                            />


                            <div>

                                <p
                                    class="
                                        text-[11px]
                                        font-medium
                                        text-slate-400
                                    "
                                >
                                    Paciente
                                </p>


                                <p
                                    class="
                                        mt-0.5
                                        text-sm
                                        font-semibold
                                        text-slate-800
                                    "
                                >
                                    {{ nombrePaciente }}
                                </p>

                            </div>

                        </div>


                        <!-- FECHA ACTUAL -->

                        <div
                            class="
                                flex
                                gap-2
                            "
                        >

                            <CalendarDays
                                :size="17"
                                class="
                                    mt-0.5
                                    shrink-0
                                    text-slate-400
                                "
                            />


                            <div>

                                <p
                                    class="
                                        text-[11px]
                                        font-medium
                                        text-slate-400
                                    "
                                >
                                    Fecha actual
                                </p>


                                <p
                                    class="
                                        mt-0.5
                                        text-sm
                                        font-semibold
                                        text-slate-800
                                    "
                                >
                                    {{
                                        formatoFechaHora(
                                            cita.fecha_hora_inicio
                                        )
                                    }}
                                </p>

                            </div>

                        </div>


                        <!-- PROFESIONAL -->

                        <div
                            class="
                                flex
                                gap-2
                            "
                        >

                            <Stethoscope
                                :size="17"
                                class="
                                    mt-0.5
                                    shrink-0
                                    text-slate-400
                                "
                            />


                            <div>

                                <p
                                    class="
                                        text-[11px]
                                        font-medium
                                        text-slate-400
                                    "
                                >
                                    Profesional actual
                                </p>


                                <p
                                    class="
                                        mt-0.5
                                        text-sm
                                        font-semibold
                                        text-slate-800
                                    "
                                >
                                    {{ nombreProfesionalActual }}
                                </p>

                            </div>

                        </div>


                        <!-- SERVICIO -->

                        <div
                            class="
                                flex
                                gap-2
                            "
                        >

                            <Timer
                                :size="17"
                                class="
                                    mt-0.5
                                    shrink-0
                                    text-slate-400
                                "
                            />


                            <div>

                                <p
                                    class="
                                        text-[11px]
                                        font-medium
                                        text-slate-400
                                    "
                                >
                                    Servicio
                                </p>


                                <p
                                    class="
                                        mt-0.5
                                        text-sm
                                        font-semibold
                                        text-slate-800
                                    "
                                >
                                    {{
                                        servicio?.nombre
                                        ??
                                        'Sin servicio'
                                    }}
                                </p>


                                <p
                                    v-if="duracionServicio"
                                    class="
                                        mt-0.5
                                        text-xs
                                        text-slate-400
                                    "
                                >
                                    {{
                                        formatoDuracion(
                                            duracionServicio
                                        )
                                    }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =============================================
                     PROFESIONAL / CONSULTORIO
                ============================================== -->

                <div
                    class="
                        grid
                        gap-4
                        sm:grid-cols-2
                    "
                >

                    <!-- PROFESIONAL -->

                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-xs
                                font-bold
                                text-slate-600
                            "
                        >
                            Profesional
                            <span class="text-rose-500">
                                *
                            </span>
                        </label>


                        <div
                            class="relative"
                        >

                            <Stethoscope
                                :size="16"
                                class="
                                    pointer-events-none
                                    absolute
                                    left-3
                                    top-1/2
                                    -translate-y-1/2
                                    text-slate-400
                                "
                            />


                            <select
                                v-model="form.profesional_id"
                                class="
                                    input-clinica
                                    w-full
                                    pl-9
                                "
                            >

                                <option value="">
                                    Seleccionar profesional
                                </option>


                                <option
                                    v-for="
                                        item
                                        in profesionales
                                    "
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.nombres }}
                                    {{ item.apellidos }}
                                </option>

                            </select>

                        </div>


                        <p
                            v-if="
                                form.errors.profesional_id
                            "
                            class="
                                mt-1
                                text-xs
                                text-rose-600
                            "
                        >
                            {{
                                form.errors.profesional_id
                            }}
                        </p>

                    </div>


                    <!-- CONSULTORIO -->

                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-xs
                                font-bold
                                text-slate-600
                            "
                        >
                            Consultorio
                        </label>


                        <div
                            class="relative"
                        >

                            <DoorOpen
                                :size="16"
                                class="
                                    pointer-events-none
                                    absolute
                                    left-3
                                    top-1/2
                                    -translate-y-1/2
                                    text-slate-400
                                "
                            />


                            <select
                                v-model="form.consultorio_id"
                                class="
                                    input-clinica
                                    w-full
                                    pl-9
                                "
                            >

                                <option value="">
                                    Sin consultorio
                                </option>


                                <option
                                    v-for="
                                        item
                                        in consultorios
                                    "
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.nombre }}
                                </option>

                            </select>

                        </div>


                        <p
                            v-if="
                                form.errors.consultorio_id
                            "
                            class="
                                mt-1
                                text-xs
                                text-rose-600
                            "
                        >
                            {{
                                form.errors.consultorio_id
                            }}
                        </p>

                    </div>

                </div>


                <!-- =============================================
                     NUEVA FECHA
                ============================================== -->

                <div>

                    <label
                        class="
                            mb-1.5
                            block
                            text-xs
                            font-bold
                            text-slate-600
                        "
                    >
                        Nueva fecha y hora

                        <span class="text-rose-500">
                            *
                        </span>
                    </label>


                    <input
                        v-model="
                            form.fecha_hora_inicio
                        "
                        type="datetime-local"
                        step="60"
                        class="
                            input-clinica
                            w-full
                        "
                    >


                    <p
                        class="
                            mt-1.5
                            text-xs
                            text-slate-400
                        "
                    >
                        Puedes seleccionar cualquier minuto.
                    </p>


                    <p
                        v-if="
                            form.errors.fecha_hora_inicio
                        "
                        class="
                            mt-1
                            text-xs
                            text-rose-600
                        "
                    >
                        {{
                            form.errors.fecha_hora_inicio
                        }}
                    </p>

                </div>


                <!-- =============================================
                     HORA FINAL
                ============================================== -->

                <div
                    v-if="
                        form.fecha_hora_inicio
                        &&
                        duracionServicio
                    "
                    class="
                        flex
                        items-center
                        gap-3
                        rounded-xl
                        border
                        border-sky-100
                        bg-sky-50
                        px-4
                        py-3
                    "
                >

                    <Clock3
                        :size="18"
                        class="
                            shrink-0
                            text-sky-600
                        "
                    />


                    <div>

                        <p
                            class="
                                text-xs
                                text-sky-600
                            "
                        >
                            Finalización estimada
                        </p>


                        <p
                            class="
                                text-sm
                                font-bold
                                text-sky-900
                            "
                        >
                            {{ horaFinal }}
                        </p>

                    </div>

                </div>


                <!-- =============================================
                     HORARIOS DISPONIBLES
                ============================================== -->

                <div
                    v-if="
                        fechaSeleccionada()
                        &&
                        form.profesional_id
                        &&
                        servicioId
                    "
                    class="
                        rounded-2xl
                        border
                        border-slate-200
                        p-4
                    "
                >

                    <div
                        class="
                            flex
                            items-center
                            justify-between
                            gap-3
                        "
                    >

                        <div>

                            <p
                                class="
                                    text-sm
                                    font-bold
                                    text-slate-800
                                "
                            >
                                Horarios disponibles
                            </p>


                            <p
                                class="
                                    mt-0.5
                                    text-xs
                                    text-slate-400
                                "
                            >
                                Sugerencias según la disponibilidad del profesional y consultorio.
                            </p>

                        </div>


                        <LoaderCircle
                            v-if="
                                cargandoHorarios
                            "
                            :size="18"
                            class="
                                animate-spin
                                text-clinica-600
                            "
                        />

                    </div>


                    <!-- ERROR -->

                    <p
                        v-if="
                            errorHorarios
                        "
                        class="
                            mt-4
                            text-xs
                            font-medium
                            text-rose-600
                        "
                    >
                        {{ errorHorarios }}
                    </p>


                    <!-- HORARIOS -->

                    <div
                        v-else-if="
                            horariosDisponibles.length
                        "
                        class="
                            mt-4
                            grid
                            grid-cols-3
                            gap-2
                            sm:grid-cols-4
                            md:grid-cols-5
                        "
                    >

                        <button
                            v-for="
                                horario
                                in horariosDisponibles
                            "
                            :key="
                                horario.inicio
                            "
                            type="button"
                            class="
                                rounded-xl
                                border
                                px-2
                                py-2.5
                                text-center
                                transition
                            "
                            :class="
                                horarioSeleccionado(
                                    horario
                                )
                                    ? [
                                        'border-clinica-600',
                                        'bg-clinica-600',
                                        'text-white',
                                        'shadow-sm',
                                    ]
                                    : [
                                        'border-slate-200',
                                        'bg-white',
                                        'text-slate-700',
                                        'hover:border-clinica-300',
                                        'hover:bg-clinica-50',
                                    ]
                            "
                            @click="
                                seleccionarHorario(
                                    horario
                                )
                            "
                        >

                            <p
                                class="
                                    text-xs
                                    font-bold
                                "
                            >
                                {{ horario.hora }}
                            </p>


                            <p
                                class="
                                    mt-0.5
                                    text-[10px]
                                    opacity-70
                                "
                            >
                                hasta
                                {{
                                    horario.fin
                                    ??
                                    '—'
                                }}
                            </p>

                        </button>

                    </div>


                    <!-- SIN HORARIOS -->

                    <div
                        v-else-if="
                            !cargandoHorarios
                        "
                        class="
                            mt-4
                            rounded-xl
                            bg-slate-50
                            px-4
                            py-5
                            text-center
                        "
                    >

                        <Clock3
                            :size="22"
                            class="
                                mx-auto
                                text-slate-300
                            "
                        />


                        <p
                            class="
                                mt-2
                                text-xs
                                text-slate-500
                            "
                        >
                            No encontramos horarios disponibles para esta fecha.
                        </p>

                    </div>

                </div>


                <!-- =============================================
                     MOTIVO
                ============================================== -->

                <div>

                    <label
                        class="
                            mb-1.5
                            block
                            text-xs
                            font-bold
                            text-slate-600
                        "
                    >
                        Motivo de reprogramación
                    </label>


                    <textarea
                        v-model="form.motivo"
                        rows="3"
                        class="
                            input-clinica
                            w-full
                            resize-none
                        "
                        placeholder="Ej. Solicitud del paciente, cambio de disponibilidad..."
                    />


                    <p
                        v-if="
                            form.errors.motivo
                        "
                        class="
                            mt-1
                            text-xs
                            text-rose-600
                        "
                    >
                        {{ form.errors.motivo }}
                    </p>

                </div>

            </div>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <footer
                class="
                    flex
                    items-center
                    justify-end
                    gap-3
                    border-t
                    border-slate-100
                    bg-slate-50/70
                    px-6
                    py-4
                "
            >

                <button
                    type="button"
                    class="
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        px-4
                        py-2.5
                        text-sm
                        font-semibold
                        text-slate-600
                        transition
                        hover:bg-slate-50
                    "
                    :disabled="
                        form.processing
                    "
                    @click="cerrar"
                >
                    Cancelar
                </button>


                <button
                    type="button"
                    class="
                        flex
                        items-center
                        gap-2
                        rounded-xl
                        bg-violet-600
                        px-4
                        py-2.5
                        text-sm
                        font-semibold
                        text-white
                        transition
                        hover:bg-violet-700
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                    "
                    :disabled="
                        form.processing
                    "
                    @click="reprogramar"
                >

                    <LoaderCircle
                        v-if="
                            form.processing
                        "
                        :size="16"
                        class="animate-spin"
                    />


                    <RotateCcw
                        v-else
                        :size="16"
                    />


                    {{
                        form.processing
                            ? 'Reprogramando...'
                            : 'Reprogramar cita'
                    }}

                </button>

            </footer>

        </section>

    </div>

</template>