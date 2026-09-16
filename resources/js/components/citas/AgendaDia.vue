<script setup>

import {
    computed,
    onMounted,
    ref,
    watch,
} from 'vue';

import {
    ChevronLeft,
    ChevronRight,
    LoaderCircle,
    Stethoscope,
    Clock3,
    DoorOpen,
    CalendarDays,
} from 'lucide-vue-next';


/* =========================================================
   PROPS
========================================================= */

const props = defineProps({

    profesionales: {
        type: Array,
        default: () => [],
    },

    fechaInicial: {
        type: String,
        default: '',
    },

    profesionalId: {
        default: '',
    },

});


/* =========================================================
   EMITS
========================================================= */

const emit = defineEmits([
    'ver-cita',
]);


/* =========================================================
   CONFIGURACIÓN DE AGENDA
========================================================= */

/*
 * Por ahora la clínica trabaja visualmente
 * entre las 08:00 y las 20:00.
 */

const HORA_INICIO = 8;

const HORA_FIN = 20;

const MINUTO_INICIO =
    HORA_INICIO * 60;

const MINUTO_FIN =
    HORA_FIN * 60;


/*
 * Cantidad de píxeles por minuto.
 *
 * 60 minutos = 72px.
 */

const MINUTO_PX = 1.2;


/* =========================================================
   ESTADO
========================================================= */

const fecha =
    ref('');

const profesional =
    ref('');

const citas =
    ref([]);

const cargando =
    ref(false);

const error =
    ref('');

let controlador =
    null;


/* =========================================================
   HOY - LIMA
========================================================= */

function hoyLima() {

    const partes =
        new Intl.DateTimeFormat(
            'en-CA',
            {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                timeZone: 'America/Lima',
            }
        ).formatToParts(
            new Date()
        );


    const obtener =
        tipo =>
            partes.find(
                item =>
                    item.type === tipo
            )?.value;


    return (
        `${obtener('year')}-`
        +
        `${obtener('month')}-`
        +
        `${obtener('day')}`
    );
}


/* =========================================================
   FECHA BONITA
========================================================= */

const fechaBonita =
    computed(() => {

        if (
            !fecha.value
        ) {
            return '';
        }


        const valor =
            new Date(
                `${fecha.value}T12:00:00`
            );


        return new Intl.DateTimeFormat(
            'es-PE',
            {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                timeZone: 'America/Lima',
            }
        ).format(
            valor
        );

    });


/* =========================================================
   PROFESIONALES VISIBLES
========================================================= */

const profesionalesVisibles =
    computed(() => {

        if (
            !profesional.value
        ) {

            return props.profesionales;
        }


        return props.profesionales.filter(
            item =>
                String(item.id)
                ===
                String(profesional.value)
        );

    });


/* =========================================================
   COLUMNAS
========================================================= */

const columnas =
    computed(() => {

        return (
            `72px repeat(`
            +
            `${profesionalesVisibles.value.length}, `
            +
            `minmax(220px, 1fr))`
        );

    });


/* =========================================================
   ANCHO MÍNIMO
========================================================= */

const anchoMinimo =
    computed(() => {

        return (
            72
            +
            (
                profesionalesVisibles.value.length
                *
                220
            )
        );

    });


/* =========================================================
   ALTO DE AGENDA
========================================================= */

const altoAgenda =
    computed(() => {

        return (
            (
                MINUTO_FIN
                -
                MINUTO_INICIO
            )
            *
            MINUTO_PX
        );

    });


/* =========================================================
   MARCAS DE HORA
========================================================= */

const marcasHora =
    computed(() => {

        const horas =
            [];


        for (
            let hora = HORA_INICIO;
            hora <= HORA_FIN;
            hora++
        ) {

            horas.push(
                hora
            );
        }


        return horas;

    });


/* =========================================================
   MOVER DÍA
========================================================= */

function moverDia(
    cantidad
) {

    if (
        !fecha.value
    ) {
        return;
    }


    const valor =
        new Date(
            `${fecha.value}T12:00:00`
        );


    valor.setDate(
        valor.getDate()
        +
        cantidad
    );


    const anio =
        valor.getFullYear();


    const mes =
        String(
            valor.getMonth() + 1
        ).padStart(
            2,
            '0'
        );


    const dia =
        String(
            valor.getDate()
        ).padStart(
            2,
            '0'
        );


    fecha.value =
        `${anio}-${mes}-${dia}`;
}


/* =========================================================
   CARGAR AGENDA
========================================================= */

async function cargarAgenda() {

    if (
        !fecha.value
    ) {
        return;
    }


    if (
        typeof window === 'undefined'
    ) {
        return;
    }


    controlador?.abort();


    controlador =
        new AbortController();


    cargando.value =
        true;


    error.value =
        '';


    try {

        const parametros =
            new URLSearchParams();


        parametros.set(
            'fecha',
            fecha.value
        );


        if (
            profesional.value
        ) {

            parametros.set(
                'profesional_id',
                profesional.value
            );
        }


        const respuesta =
            await fetch(
                `/clinica/citas/agenda?${parametros.toString()}`,
                {
                    signal:
                        controlador.signal,

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
                'Respuesta Agenda Día:',
                texto
            );


            throw new Error(
                `HTTP ${respuesta.status}`
            );
        }


        const datos =
            await respuesta.json();


        citas.value =
            datos.citas
            ??
            [];

    }
    catch (
        excepcion
    ) {

        if (
            excepcion.name === 'AbortError'
        ) {
            return;
        }


        console.error(
            'Error cargando Agenda Día:',
            excepcion
        );


        citas.value =
            [];


        error.value =
            'No se pudo cargar la agenda del día.';

    }
    finally {

        cargando.value =
            false;
    }
}


/* =========================================================
   CITAS DE UN PROFESIONAL
========================================================= */

function citasProfesional(
    profesionalId
) {

    return citas.value.filter(
        cita =>
            String(
                cita.profesional_id
                ??
                cita.profesional?.id
            )
            ===
            String(
                profesionalId
            )
    );
}


/* =========================================================
   SERVICIO
========================================================= */

function servicioNombre(
    cita
) {

    return (
        cita
            ?.servicios_cita
            ?.[0]
            ?.servicio
            ?.nombre
        ??
        'Sin servicio'
    );
}


/* =========================================================
   OBTENER MINUTOS
========================================================= */

function horaTextoAMinutos(
    valor
) {

    if (
        !valor
    ) {
        return null;
    }


    const coincidencia =
        String(
            valor
        ).match(
            /(\d{1,2}):(\d{2})/
        );


    if (
        !coincidencia
    ) {
        return null;
    }


    return (
        Number(
            coincidencia[1]
        )
        *
        60
        +
        Number(
            coincidencia[2]
        )
    );
}


/* =========================================================
   MINUTO INICIO DE CITA
========================================================= */

function minutoInicioCita(
    cita
) {

    const directo =
        Number(
            cita.agenda_inicio_minutos
        );


    if (
        Number.isFinite(
            directo
        )
    ) {

        return directo;
    }


    return (
        horaTextoAMinutos(
            cita.agenda_inicio_texto
        )
        ??
        MINUTO_INICIO
    );
}


/* =========================================================
   MINUTO FIN DE CITA
========================================================= */

function minutoFinCita(
    cita
) {

    const directo =
        Number(
            cita.agenda_fin_minutos
        );


    if (
        Number.isFinite(
            directo
        )
    ) {

        return directo;
    }


    return (
        horaTextoAMinutos(
            cita.agenda_fin_texto
        )
        ??
        (
            minutoInicioCita(
                cita
            )
            +
            30
        )
    );
}


/* =========================================================
   DURACIÓN REAL VISUAL
========================================================= */

function duracionCita(
    cita
) {

    const inicio =
        minutoInicioCita(
            cita
        );


    const fin =
        minutoFinCita(
            cita
        );


    return Math.max(
        fin - inicio,
        1
    );
}


/* =========================================================
   TIPO DE TARJETA
========================================================= */

function esCitaCompacta(
    cita
) {

    return (
        duracionCita(
            cita
        )
        <=
        30
    );
}


function esCitaMedia(
    cita
) {

    const duracion =
        duracionCita(
            cita
        );


    return (
        duracion > 30
        &&
        duracion <= 60
    );
}


/* =========================================================
   POSICIÓN DE CITA
========================================================= */

function estiloCita(
    cita
) {

    const inicio =
        minutoInicioCita(
            cita
        );


    const duracion =
        duracionCita(
            cita
        );


    const top =
        (
            inicio
            -
            MINUTO_INICIO
        )
        *
        MINUTO_PX;


    /*
     * Altura mínima visual según duración.
     *
     * Una cita de 15 minutos necesita algo
     * de espacio para ser legible.
     */

    let altoMinimo =
        44;


    if (
        duracion > 30
    ) {

        altoMinimo =
            66;
    }


    if (
        duracion > 60
    ) {

        altoMinimo =
            88;
    }


    const alto =
        Math.max(
            duracion
            *
            MINUTO_PX,

            altoMinimo
        );


    return {

        top:
            `${Math.max(top, 0)}px`,

        height:
            `${alto}px`,

    };
}


/* =========================================================
   COLOR DE ESTADO
========================================================= */

function claseCita(
    codigo
) {

    switch (
        codigo
    ) {

        case 'PENDIENTE':

            return [
                'border-amber-200',
                'bg-amber-50',
                'text-amber-950',
                'hover:bg-amber-100',
            ];


        case 'CONFIRMADA':

            return [
                'border-sky-200',
                'bg-sky-50',
                'text-sky-950',
                'hover:bg-sky-100',
            ];


        case 'EN_ATENCION':

            return [
                'border-violet-200',
                'bg-violet-50',
                'text-violet-950',
                'hover:bg-violet-100',
            ];


        case 'ATENDIDA':

            return [
                'border-emerald-200',
                'bg-emerald-50',
                'text-emerald-950',
                'hover:bg-emerald-100',
            ];


        default:

            return [
                'border-slate-200',
                'bg-slate-50',
                'text-slate-800',
                'hover:bg-slate-100',
            ];
    }
}


/* =========================================================
   WATCH
========================================================= */

watch(
    [
        fecha,
        profesional,
    ],

    () => {

        cargarAgenda();

    }
);


/* =========================================================
   SINCRONIZAR FECHA EXTERNA
========================================================= */

watch(
    () =>
        props.fechaInicial,

    nuevaFecha => {

        if (
            nuevaFecha
            &&
            nuevaFecha !== fecha.value
        ) {

            fecha.value =
                nuevaFecha;
        }

    }
);


/* =========================================================
   SINCRONIZAR PROFESIONAL EXTERNO
========================================================= */

watch(
    () =>
        props.profesionalId,

    nuevoProfesional => {

        profesional.value =
            nuevoProfesional
            ??
            '';

    }
);


/* =========================================================
   INICIO
========================================================= */

onMounted(
    () => {

        profesional.value =
            props.profesionalId
            ??
            '';


        fecha.value =
            props.fechaInicial
            ||
            hoyLima();

    }
);

</script>


<template>

    <section
        class="
            mt-5
            overflow-hidden
            rounded-2xl
            border
            border-slate-200
            bg-white
            shadow-sm
        "
    >

        <!-- =================================================
             HEADER
        ================================================== -->

        <div
            class="
                flex
                flex-col
                gap-4
                border-b
                border-slate-100
                p-4
                lg:flex-row
                lg:items-center
                lg:justify-between
            "
        >

            <!-- NAVEGACIÓN DE FECHA -->

            <div
                class="
                    flex
                    flex-wrap
                    items-center
                    gap-2
                "
            >

                <button
                    type="button"
                    class="
                        flex
                        h-9
                        w-9
                        items-center
                        justify-center
                        rounded-lg
                        border
                        border-slate-200
                        bg-white
                        text-slate-500
                        transition
                        hover:bg-slate-50
                    "
                    @click="
                        moverDia(-1)
                    "
                >
                    <ChevronLeft
                        :size="18"
                    />
                </button>


                <div
                    class="
                        min-w-60
                        text-center
                    "
                >

                    <p
                        class="
                            text-sm
                            font-bold
                            capitalize
                            text-slate-900
                        "
                    >
                        {{ fechaBonita }}
                    </p>

                </div>


                <button
                    type="button"
                    class="
                        flex
                        h-9
                        w-9
                        items-center
                        justify-center
                        rounded-lg
                        border
                        border-slate-200
                        bg-white
                        text-slate-500
                        transition
                        hover:bg-slate-50
                    "
                    @click="
                        moverDia(1)
                    "
                >
                    <ChevronRight
                        :size="18"
                    />
                </button>


                <button
                    type="button"
                    class="
                        ml-1
                        rounded-lg
                        bg-slate-100
                        px-3
                        py-2
                        text-xs
                        font-semibold
                        text-slate-600
                        transition
                        hover:bg-slate-200
                    "
                    @click="
                        fecha = hoyLima()
                    "
                >
                    Hoy
                </button>

            </div>


            <!-- FILTROS -->

            <div
                class="
                    flex
                    flex-col
                    gap-2
                    sm:flex-row
                "
            >

                <input
                    v-model="fecha"
                    type="date"
                    class="input-clinica"
                >


                <select
                    v-model="profesional"
                    class="
                        input-clinica
                        min-w-56
                    "
                >

                    <option value="">
                        Todos los profesionales
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

        </div>


        <!-- =================================================
             LOADING
        ================================================== -->

        <div
            v-if="cargando"
            class="
                flex
                items-center
                justify-center
                gap-3
                p-12
                text-sm
                text-slate-500
            "
        >

            <LoaderCircle
                :size="20"
                class="animate-spin"
            />

            Cargando agenda...

        </div>


        <!-- =================================================
             ERROR
        ================================================== -->

        <div
            v-else-if="error"
            class="
                p-12
                text-center
            "
        >

            <CalendarDays
                :size="32"
                class="
                    mx-auto
                    text-rose-300
                "
            />


            <p
                class="
                    mt-3
                    text-sm
                    font-semibold
                    text-rose-600
                "
            >
                {{ error }}
            </p>

        </div>


        <!-- =================================================
             SIN PROFESIONALES
        ================================================== -->

        <div
            v-else-if="
                !profesionalesVisibles.length
            "
            class="
                p-12
                text-center
                text-sm
                text-slate-500
            "
        >
            No hay profesionales disponibles.
        </div>


        <!-- =================================================
             AGENDA
        ================================================== -->

        <div
            v-else
            class="overflow-x-auto"
        >

            <div
                :style="{
                    minWidth:
                        `${anchoMinimo}px`
                }"
            >

                <!-- =========================================
                     HEADER PROFESIONALES
                ========================================== -->

                <div
                    class="
                        grid
                        border-b
                        border-slate-200
                        bg-slate-50
                    "
                    :style="{
                        gridTemplateColumns:
                            columnas
                    }"
                >

                    <!-- HORA -->

                    <div
                        class="
                            flex
                            items-center
                            justify-center
                            border-r
                            border-slate-200
                            p-3
                            text-xs
                            font-semibold
                            text-slate-400
                        "
                    >
                        Hora
                    </div>


                    <!-- PROFESIONALES -->

                    <div
                        v-for="
                            item
                            in profesionalesVisibles
                        "
                        :key="item.id"
                        class="
                            border-r
                            border-slate-200
                            p-3
                            text-center
                        "
                    >

                        <div
                            class="
                                flex
                                items-center
                                justify-center
                                gap-2
                            "
                        >

                            <Stethoscope
                                :size="15"
                                class="text-clinica-600"
                            />


                            <span
                                class="
                                    truncate
                                    text-sm
                                    font-bold
                                    text-slate-700
                                "
                            >
                                {{ item.nombres }}
                                {{ item.apellidos }}
                            </span>

                        </div>

                    </div>

                </div>


                <!-- =========================================
                     CUERPO
                ========================================== -->

                <div
                    class="relative"
                    :style="{
                        height:
                            `${altoAgenda}px`
                    }"
                >

                    <!-- =====================================
                         LÍNEAS DE HORAS
                    ====================================== -->

                    <div
                        v-for="
                            hora
                            in marcasHora
                        "
                        :key="hora"
                        class="
                            absolute
                            left-0
                            right-0
                            border-t
                            border-slate-100
                        "
                        :style="{
                            top:
                                `${
                                    (
                                        hora * 60
                                        -
                                        MINUTO_INICIO
                                    )
                                    *
                                    MINUTO_PX
                                }px`
                        }"
                    >

                        <span
                            class="
                                absolute
                                left-0
                                top-0
                                w-[72px]
                                -translate-y-1/2
                                bg-white
                                pr-3
                                text-right
                                text-[11px]
                                font-medium
                                text-slate-400
                            "
                        >
                            {{
                                String(
                                    hora
                                ).padStart(
                                    2,
                                    '0'
                                )
                            }}:00
                        </span>

                    </div>


                    <!-- =====================================
                         COLUMNAS DE PROFESIONALES
                    ====================================== -->

                    <div
                        class="
                            absolute
                            bottom-0
                            left-[72px]
                            right-0
                            top-0
                            grid
                        "
                        :style="{
                            gridTemplateColumns:
                                `repeat(
                                    ${profesionalesVisibles.length},
                                    minmax(220px, 1fr)
                                )`
                        }"
                    >

                        <div
                            v-for="
                                item
                                in profesionalesVisibles
                            "
                            :key="item.id"
                            class="
                                relative
                                border-l
                                border-slate-100
                            "
                        >

                            <!-- =================================
                                 CITAS
                            ================================== -->

                            <button
                                v-for="
                                    cita
                                    in citasProfesional(
                                        item.id
                                    )
                                "
                                :key="cita.id"
                                type="button"
                                class="
                                    absolute
                                    left-2
                                    right-2
                                    overflow-hidden
                                    rounded-xl
                                    border
                                    p-2.5
                                    text-left
                                    shadow-sm
                                    transition
                                    hover:z-20
                                    hover:-translate-y-[1px]
                                    hover:shadow-md
                                "
                                :class="
                                    claseCita(
                                        cita.estado_cita
                                            ?.codigo
                                    )
                                "
                                :style="
                                    estiloCita(
                                        cita
                                    )
                                "
                                @click="
                                    emit(
                                        'ver-cita',
                                        cita
                                    )
                                "
                            >

                                <!-- =============================
                                     CITA COMPACTA <= 30 MIN
                                ============================== -->

                                <template
                                    v-if="
                                        esCitaCompacta(
                                            cita
                                        )
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            h-full
                                            min-w-0
                                            flex-col
                                            justify-center
                                        "
                                    >

                                        <p
                                            class="
                                                truncate
                                                text-xs
                                                font-bold
                                            "
                                        >
                                            {{
                                                cita.paciente
                                                    ?.nombres
                                            }}

                                            {{
                                                cita.paciente
                                                    ?.apellidos
                                            }}
                                        </p>


                                        <p
                                            class="
                                                mt-0.5
                                                truncate
                                                text-[10px]
                                                font-medium
                                                opacity-75
                                            "
                                        >
                                            {{
                                                cita.agenda_inicio_texto
                                            }}

                                            ·

                                            {{
                                                servicioNombre(
                                                    cita
                                                )
                                            }}
                                        </p>

                                    </div>

                                </template>


                                <!-- =============================
                                     CITA MEDIA 31 - 60 MIN
                                ============================== -->

                                <template
                                    v-else-if="
                                        esCitaMedia(
                                            cita
                                        )
                                    "
                                >

                                    <p
                                        class="
                                            truncate
                                            text-xs
                                            font-bold
                                        "
                                    >
                                        {{
                                            cita.paciente
                                                ?.nombres
                                        }}

                                        {{
                                            cita.paciente
                                                ?.apellidos
                                        }}
                                    </p>


                                    <p
                                        class="
                                            mt-0.5
                                            truncate
                                            text-[11px]
                                            opacity-80
                                        "
                                    >
                                        {{
                                            servicioNombre(
                                                cita
                                            )
                                        }}
                                    </p>


                                    <div
                                        class="
                                            mt-1
                                            flex
                                            items-center
                                            gap-1
                                            text-[10px]
                                            font-medium
                                            opacity-70
                                        "
                                    >

                                        <Clock3
                                            :size="11"
                                        />

                                        {{
                                            cita.agenda_inicio_texto
                                        }}

                                        <span>
                                            —
                                        </span>

                                        {{
                                            cita.agenda_fin_texto
                                            ??
                                            '—'
                                        }}

                                    </div>

                                </template>


                                <!-- =============================
                                     CITA LARGA > 60 MIN
                                ============================== -->

                                <template
                                    v-else
                                >

                                    <p
                                        class="
                                            truncate
                                            text-xs
                                            font-bold
                                        "
                                    >
                                        {{
                                            cita.paciente
                                                ?.nombres
                                        }}

                                        {{
                                            cita.paciente
                                                ?.apellidos
                                        }}
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            truncate
                                            text-[11px]
                                            opacity-80
                                        "
                                    >
                                        {{
                                            servicioNombre(
                                                cita
                                            )
                                        }}
                                    </p>


                                    <div
                                        class="
                                            mt-1
                                            flex
                                            items-center
                                            gap-1
                                            text-[10px]
                                            font-medium
                                            opacity-70
                                        "
                                    >

                                        <Clock3
                                            :size="11"
                                        />

                                        {{
                                            cita.agenda_inicio_texto
                                        }}

                                        <span>
                                            —
                                        </span>

                                        {{
                                            cita.agenda_fin_texto
                                            ??
                                            '—'
                                        }}

                                    </div>


                                    <div
                                        v-if="
                                            cita.consultorio
                                        "
                                        class="
                                            mt-1
                                            flex
                                            items-center
                                            gap-1
                                            truncate
                                            text-[10px]
                                            opacity-70
                                        "
                                    >

                                        <DoorOpen
                                            :size="11"
                                        />

                                        <span
                                            class="truncate"
                                        >
                                            {{
                                                cita.consultorio
                                                    .nombre
                                            }}
                                        </span>

                                    </div>

                                </template>

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

</template>