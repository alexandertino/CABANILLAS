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
    CalendarDays,
    LoaderCircle,
    Clock3,
    Stethoscope,
    DoorOpen,
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
    'ver-dia',
]);


/* =========================================================
   ESTADO
========================================================= */

const fecha =
    ref('');

const profesional =
    ref('');

const citas =
    ref([]);

const dias =
    ref([]);

const inicioSemana =
    ref('');

const finSemana =
    ref('');

const cargando =
    ref(false);

const error =
    ref('');

let controlador =
    null;


/* =========================================================
   HOY EN LIMA
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


    const obtener = tipo =>
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
   SABER SI ES HOY
========================================================= */

function esHoy(
    valor
) {

    return valor === hoyLima();
}


/* =========================================================
   CITAS POR DÍA
========================================================= */

function citasDia(
    valorFecha
) {

    return citas.value.filter(
        cita =>
            cita.agenda_fecha
            ===
            valorFecha
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
   COLOR SEGÚN ESTADO
========================================================= */

function claseEstado(
    codigo
) {

    switch (codigo) {

        case 'PENDIENTE':

            return [
                'border-amber-200',
                'bg-amber-50',
                'text-amber-900',
            ];


        case 'CONFIRMADA':

            return [
                'border-blue-200',
                'bg-blue-50',
                'text-blue-900',
            ];


        case 'EN_ATENCION':

            return [
                'border-violet-200',
                'bg-violet-50',
                'text-violet-900',
            ];


        case 'ATENDIDA':

            return [
                'border-emerald-200',
                'bg-emerald-50',
                'text-emerald-900',
            ];


        default:

            return [
                'border-slate-200',
                'bg-slate-50',
                'text-slate-800',
            ];
    }
}


/* =========================================================
   NOMBRE DEL DÍA
========================================================= */

function nombreDia(
    valor
) {

    if (!valor) {
        return '';
    }


    return new Intl.DateTimeFormat(
        'es-PE',
        {
            weekday: 'short',
            timeZone: 'America/Lima',
        }
    ).format(
        new Date(
            `${valor}T12:00:00`
        )
    );
}


/* =========================================================
   NÚMERO DEL DÍA
========================================================= */

function numeroDia(
    valor
) {

    if (!valor) {
        return '';
    }


    return new Intl.DateTimeFormat(
        'es-PE',
        {
            day: '2-digit',
            timeZone: 'America/Lima',
        }
    ).format(
        new Date(
            `${valor}T12:00:00`
        )
    );
}


/* =========================================================
   MES
========================================================= */

function nombreMes(
    valor
) {

    if (!valor) {
        return '';
    }


    return new Intl.DateTimeFormat(
        'es-PE',
        {
            month: 'short',
            timeZone: 'America/Lima',
        }
    ).format(
        new Date(
            `${valor}T12:00:00`
        )
    );
}


/* =========================================================
   TÍTULO DE SEMANA
========================================================= */

const tituloSemana =
    computed(() => {

        if (
            !inicioSemana.value
            ||
            !finSemana.value
        ) {

            return '';
        }


        const inicio =
            new Date(
                `${inicioSemana.value}T12:00:00`
            );


        const fin =
            new Date(
                `${finSemana.value}T12:00:00`
            );


        const inicioTexto =
            new Intl.DateTimeFormat(
                'es-PE',
                {
                    day: '2-digit',
                    month: 'short',
                    timeZone: 'America/Lima',
                }
            ).format(
                inicio
            );


        const finTexto =
            new Intl.DateTimeFormat(
                'es-PE',
                {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    timeZone: 'America/Lima',
                }
            ).format(
                fin
            );


        return (
            `${inicioTexto} — ${finTexto}`
        );

    });


/* =========================================================
   TOTAL CITAS
========================================================= */

const totalCitas =
    computed(() => {

        return citas.value.length;

    });


/* =========================================================
   CARGAR SEMANA
========================================================= */

async function cargarAgenda() {

    if (
        !fecha.value
    ) {
        return;
    }


    if (
        typeof window
        ===
        'undefined'
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
                `/clinica/citas/agenda-semana?${parametros.toString()}`,
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
                'Respuesta de agenda semanal:',
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


        dias.value =
            datos.dias
            ??
            [];


        inicioSemana.value =
            datos.inicio_semana
            ??
            '';


        finSemana.value =
            datos.fin_semana
            ??
            '';

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
            'Error cargando agenda semanal:',
            excepcion
        );


        error.value =
            'No se pudo cargar la agenda semanal.';

    }
    finally {

        cargando.value =
            false;
    }
}


/* =========================================================
   CAMBIAR SEMANA
========================================================= */

function moverSemana(
    cantidad
) {

    const valor =
        new Date(
            `${fecha.value}T12:00:00`
        );


    valor.setDate(
        valor.getDate()
        +
        (
            cantidad * 7
        )
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
   ESTA SEMANA
========================================================= */

function irEstaSemana() {

    fecha.value =
        hoyLima();
}


/* =========================================================
   ABRIR UN DÍA
========================================================= */

function abrirDia(
    valorFecha
) {

    emit(
        'ver-dia',
        valorFecha
    );
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
   INIT
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

        <header
            class="
                flex
                flex-col
                gap-4
                border-b
                border-slate-200
                p-4
                lg:flex-row
                lg:items-center
                lg:justify-between
            "
        >

            <!-- NAVEGACIÓN -->

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
                        h-10
                        w-10
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        text-slate-500
                        transition
                        hover:bg-slate-50
                    "
                    @click="
                        moverSemana(-1)
                    "
                >

                    <ChevronLeft
                        :size="18"
                    />

                </button>


                <div
                    class="
                        min-w-52
                        px-2
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
                        {{ tituloSemana }}
                    </p>


                    <p
                        class="
                            mt-0.5
                            text-xs
                            text-slate-400
                        "
                    >
                        {{ totalCitas }}

                        {{
                            totalCitas === 1
                                ? 'cita'
                                : 'citas'
                        }}
                    </p>

                </div>


                <button
                    type="button"
                    class="
                        flex
                        h-10
                        w-10
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        text-slate-500
                        transition
                        hover:bg-slate-50
                    "
                    @click="
                        moverSemana(1)
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
                        rounded-xl
                        bg-slate-100
                        px-3
                        py-2.5
                        text-xs
                        font-semibold
                        text-slate-600
                        transition
                        hover:bg-slate-200
                    "
                    @click="
                        irEstaSemana
                    "
                >
                    Esta semana
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
                    v-model="
                        fecha
                    "
                    type="date"
                    class="input-clinica"
                >


                <select
                    v-model="
                        profesional
                    "
                    class="
                        input-clinica
                        min-w-60
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
                        :key="
                            item.id
                        "
                        :value="
                            item.id
                        "
                    >
                        {{ item.nombres }}
                        {{ item.apellidos }}
                    </option>

                </select>

            </div>

        </header>


        <!-- =================================================
             LOADING
        ================================================== -->

        <div
            v-if="
                cargando
            "
            class="
                flex
                items-center
                justify-center
                gap-3
                py-20
                text-sm
                text-slate-500
            "
        >

            <LoaderCircle
                :size="20"
                class="animate-spin"
            />

            Cargando agenda semanal...

        </div>


        <!-- =================================================
             ERROR
        ================================================== -->

        <div
            v-else-if="
                error
            "
            class="
                py-20
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
             SEMANA
        ================================================== -->

        <div
            v-else
            class="
                overflow-x-auto
            "
        >

            <div
                class="
                    grid
                    min-w-[1260px]
                    grid-cols-7
                    divide-x
                    divide-slate-100
                "
            >

                <section
                    v-for="
                        dia
                        in dias
                    "
                    :key="
                        dia.fecha
                    "
                    class="
                        min-h-[500px]
                        bg-white
                    "
                >

                    <!-- =====================================
                         CABECERA DEL DÍA
                    ====================================== -->

                    <button
                        type="button"
                        class="
                            group
                            block
                            w-full
                            border-b
                            border-slate-100
                            p-3
                            text-center
                            transition
                            hover:bg-clinica-50
                        "
                        :class="
                            esHoy(
                                dia.fecha
                            )
                                ? 'bg-clinica-50'
                                : 'bg-slate-50'
                        "
                        @click="
                            abrirDia(
                                dia.fecha
                            )
                        "
                    >

                        <p
                            class="
                                text-xs
                                font-semibold
                                uppercase
                                capitalize
                                text-slate-400
                                group-hover:text-clinica-600
                            "
                        >
                            {{
                                nombreDia(
                                    dia.fecha
                                )
                            }}
                        </p>


                        <div
                            class="
                                mt-1
                                flex
                                items-center
                                justify-center
                            "
                        >

                            <span
                                class="
                                    flex
                                    h-9
                                    w-9
                                    items-center
                                    justify-center
                                    rounded-full
                                    text-lg
                                    font-bold
                                "
                                :class="
                                    esHoy(
                                        dia.fecha
                                    )
                                        ? [
                                            'bg-clinica-700',
                                            'text-white',
                                        ]
                                        : [
                                            'text-slate-800',
                                        ]
                                "
                            >
                                {{
                                    numeroDia(
                                        dia.fecha
                                    )
                                }}
                            </span>

                        </div>


                        <p
                            class="
                                mt-1
                                text-[11px]
                                capitalize
                                text-slate-400
                            "
                        >
                            {{
                                nombreMes(
                                    dia.fecha
                                )
                            }}
                        </p>


                        <p
                            class="
                                mt-2
                                text-[10px]
                                font-medium
                                text-slate-400
                            "
                        >
                            {{
                                citasDia(
                                    dia.fecha
                                ).length
                            }}

                            {{
                                citasDia(
                                    dia.fecha
                                ).length === 1
                                    ? 'cita'
                                    : 'citas'
                            }}
                        </p>

                    </button>


                    <!-- =====================================
                         CITAS
                    ====================================== -->

                    <div
                        class="
                            space-y-2
                            p-2
                        "
                    >

                        <button
                            v-for="
                                cita
                                in citasDia(
                                    dia.fecha
                                )
                            "
                            :key="
                                cita.id
                            "
                            type="button"
                            class="
                                block
                                w-full
                                rounded-xl
                                border
                                p-3
                                text-left
                                shadow-sm
                                transition
                                hover:-translate-y-0.5
                                hover:shadow-md
                            "
                            :class="
                                claseEstado(
                                    cita.estado_cita
                                        ?.codigo
                                )
                            "
                            @click="
                                emit(
                                    'ver-cita',
                                    cita
                                )
                            "
                        >

                            <!-- HORA -->

                            <div
                                class="
                                    flex
                                    items-center
                                    gap-1
                                    text-[11px]
                                    font-bold
                                    opacity-80
                                "
                            >

                                <Clock3
                                    :size="12"
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


                            <!-- PACIENTE -->

                            <p
                                class="
                                    mt-2
                                    truncate
                                    text-sm
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


                            <!-- SERVICIO -->

                            <p
                                class="
                                    mt-1
                                    truncate
                                    text-xs
                                    opacity-80
                                "
                            >
                                {{
                                    servicioNombre(
                                        cita
                                    )
                                }}
                            </p>


                            <!-- PROFESIONAL -->

                            <div
                                class="
                                    mt-2
                                    flex
                                    items-center
                                    gap-1
                                    truncate
                                    text-[11px]
                                    opacity-70
                                "
                            >

                                <Stethoscope
                                    :size="11"
                                />

                                <span
                                    class="truncate"
                                >
                                    {{
                                        cita.profesional
                                            ?.nombres
                                    }}

                                    {{
                                        cita.profesional
                                            ?.apellidos
                                    }}
                                </span>

                            </div>


                            <!-- CONSULTORIO -->

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
                                    text-[11px]
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


                            <!-- ESTADO -->

                            <p
                                class="
                                    mt-2
                                    text-[10px]
                                    font-bold
                                    uppercase
                                    tracking-wide
                                    opacity-60
                                "
                            >
                                {{
                                    cita.estado_cita
                                        ?.nombre
                                }}
                            </p>

                        </button>


                        <!-- SIN CITAS -->

                        <div
                            v-if="
                                !citasDia(
                                    dia.fecha
                                ).length
                            "
                            class="
                                flex
                                min-h-28
                                items-center
                                justify-center
                                text-center
                            "
                        >

                            <p
                                class="
                                    text-xs
                                    text-slate-300
                                "
                            >
                                Sin citas
                            </p>

                        </div>

                    </div>

                </section>

            </div>

        </div>

    </section>

</template>