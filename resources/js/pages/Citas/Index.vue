<script setup>
import AgendaDia
    from '@/components/citas/AgendaDia.vue';

import AgendaSemana
    from '@/components/citas/AgendaSemana.vue';
    
import {
    computed,
    ref,
    watch,
} from 'vue';

import {
    Head,
    Link,
    router,
} from '@inertiajs/vue3';

import {
    CalendarDays,
    CalendarClock,
    Plus,
    Search,
    Clock3,
    Stethoscope,
    DoorOpen,
    UserRound,
    CheckCircle2,
    Play,
    Flag,
    CalendarX,
    Pencil,
    LoaderCircle,
    Eye,
    EllipsisVertical,
} from 'lucide-vue-next';

import AppLayout
    from '@/layouts/AppLayout.vue';

import CitaFormDrawer
    from '@/components/citas/CitaFormDrawer.vue';

import CancelarCitaModal
    from '@/components/citas/CancelarCitaModal.vue';

import ReprogramarCitaModal
    from '@/components/citas/ReprogramarCitaModal.vue';

import CitaDetalleDrawer
    from '@/components/citas/CitaDetalleDrawer.vue';

import FinalizarAtencionModal
    from '@/components/citas/FinalizarAtencionModal.vue';


/* =========================================================
   PROPS
========================================================= */

const props = defineProps({

    citas: {
        type: Object,
        required: true,
    },

    profesionales: {
        type: Array,
        default: () => [],
    },

    consultorios: {
        type: Array,
        default: () => [],
    },

    estados: {
        type: Array,
        default: () => [],
    },

    estadoPendiente: {
        default: null,
    },

    filtros: {
        type: Object,
        default: () => ({}),
    },

});

const modoVista =
    ref('lista');

/* =========================================================
   FILTROS
========================================================= */

const buscar = ref(
    props.filtros.buscar ?? ''
);

const fecha = ref(
    props.filtros.fecha ?? ''
);

const estado = ref(
    props.filtros.estado ?? ''
);

const profesional = ref(
    props.filtros.profesional ?? ''
);

let temporizador = null;


/* =========================================================
   DRAWER CREAR / EDITAR
========================================================= */

const drawerAbierto =
    ref(false);

const citaSeleccionada =
    ref(null);


/* =========================================================
   MODAL CANCELAR
========================================================= */

const cancelarModalAbierto =
    ref(false);

const citaCancelar =
    ref(null);


/* =========================================================
   MODAL REPROGRAMAR
========================================================= */

const reprogramarModalAbierto =
    ref(false);

const citaReprogramar =
    ref(null);


/* =========================================================
   DRAWER DETALLE
========================================================= */

const detalleAbierto =
    ref(false);

const citaDetalle =
    ref(null);


/* =========================================================
   MODAL FINALIZAR ATENCIÓN
========================================================= */

const finalizarModalAbierto =
    ref(false);

const citaFinalizar =
    ref(null);


/* =========================================================
   CAMBIO DE ESTADO
========================================================= */

const cambiandoEstado =
    ref(null);


/* =========================================================
   COMPUTED
========================================================= */

const hayCitas = computed(() => {

    return (
        props.citas?.data?.length ?? 0
    ) > 0;

});


/* =========================================================
   WATCHERS
========================================================= */

watch(
    buscar,

    () => {

        clearTimeout(
            temporizador
        );


        if (
            modoVista.value
            !==
            'lista'
        ) {
            return;
        }


        temporizador =
            setTimeout(
                aplicarFiltros,
                400
            );

    }
);

watch(
    [
        fecha,
        estado,
        profesional,
    ],

    () => {

        if (
            modoVista.value
            ===
            'lista'
        ) {

            aplicarFiltros();
        }

    }
);


/* =========================================================
   FILTROS
========================================================= */

function aplicarFiltros() {

    router.get(
        '/clinica/citas',

        {
            buscar:
                buscar.value ||
                undefined,

            fecha:
                fecha.value ||
                undefined,

            estado:
                estado.value ||
                undefined,

            profesional:
                profesional.value ||
                undefined,
        },

        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}


/* =========================================================
   NUEVA CITA
========================================================= */

function nuevaCita() {

    citaSeleccionada.value =
        null;

    drawerAbierto.value =
        true;
}


/* =========================================================
   EDITAR
========================================================= */

function editarCita(
    cita
) {

    citaSeleccionada.value =
        cita;

    drawerAbierto.value =
        true;
}


function cerrarDrawer() {

    drawerAbierto.value =
        false;

    citaSeleccionada.value =
        null;
}


/* =========================================================
   CANCELAR
========================================================= */

function abrirCancelar(
    cita
) {

    citaCancelar.value =
        cita;

    cancelarModalAbierto.value =
        true;
}


function cerrarCancelar() {

    cancelarModalAbierto.value =
        false;

    citaCancelar.value =
        null;
}


/* =========================================================
   REPROGRAMAR
========================================================= */

function abrirReprogramar(
    cita
) {

    citaReprogramar.value =
        cita;

    reprogramarModalAbierto.value =
        true;
}


function cerrarReprogramar() {

    reprogramarModalAbierto.value =
        false;

    citaReprogramar.value =
        null;
}


/* =========================================================
   DETALLE
========================================================= */

function abrirDetalle(
    cita
) {

    citaDetalle.value =
        cita;

    detalleAbierto.value =
        true;
}


function cerrarDetalle() {

    detalleAbierto.value =
        false;

    citaDetalle.value =
        null;
}


/* =========================================================
   ESTADOS
========================================================= */

function obtenerEstado(
    codigo
) {

    return props.estados.find(
        item =>
            item.codigo === codigo
    );
}


function codigoEstado(
    cita
) {

    return (
        cita
            ?.estado_cita
            ?.codigo
        ?? ''
    );
}


/* =========================================================
   CAMBIAR ESTADO
========================================================= */

function cambiarEstado(
    cita,
    codigo,
    motivo = null
) {

    if (!cita?.id) {

        console.error(
            'La cita no tiene un ID válido.'
        );

        return;
    }


    const nuevoEstado =
        obtenerEstado(
            codigo
        );


    if (!nuevoEstado) {

        console.error(
            `No existe el estado ${codigo}`
        );

        return;
    }


    cambiandoEstado.value =
        cita.id;


    router.patch(
        `/clinica/citas/${cita.id}/estado`,

        {
            estado_cita_id:
                nuevoEstado.id,

            motivo:
                motivo,
        },

        {
            preserveScroll: true,

            onSuccess: () => {

                console.log(
                    `Cita cambiada a ${codigo}`
                );
            },

            onError: (
                errores
            ) => {

                console.error(
                    'Error cambiando estado:',
                    errores
                );
            },

            onFinish: () => {

                cambiandoEstado.value =
                    null;
            },
        }
    );
}


/* =========================================================
   CONFIRMAR
========================================================= */

function confirmarCita(
    cita
) {

    cambiarEstado(
        cita,
        'CONFIRMADA',
        'Cita confirmada'
    );
}


/* =========================================================
   INICIAR ATENCIÓN
========================================================= */

function iniciarAtencion(
    cita
) {

    cambiarEstado(
        cita,
        'EN_ATENCION',
        'Inicio de atención'
    );
}


/* =========================================================
   FINALIZAR ATENCIÓN
========================================================= */

function citaTieneTratamiento(
    cita
) {

    return Boolean(
        cita?.tratamiento_cita
        ??
        cita?.tratamientoCita
    );
}


function cerrarFinalizarAtencion() {

    finalizarModalAbierto.value =
        false;

    citaFinalizar.value =
        null;
}


function finalizarAtencion(
    cita
) {

    if (
        !cita?.id
    ) {

        console.error(
            'La cita no tiene un ID válido.'
        );

        return;
    }


    /*
     * Si la cita pertenece a un tratamiento,
     * preguntamos qué ocurrirá con él.
     */

    if (
        citaTieneTratamiento(
            cita
        )
    ) {

        citaFinalizar.value =
            cita;

        finalizarModalAbierto.value =
            true;

        return;
    }


    /*
     * Una cita simple se finaliza directamente
     * usando el endpoint especializado.
     */

    cambiandoEstado.value =
        cita.id;


    router.patch(
        `/clinica/citas/${cita.id}/finalizar-atencion`,
        {},

        {
            preserveScroll:
                true,

            onError: errores => {

                console.error(
                    'Error finalizando atención:',
                    errores
                );
            },

            onFinish: () => {

                cambiandoEstado.value =
                    null;
            },
        }
    );
}


/* =========================================================
   NO ASISTIÓ
========================================================= */

function marcarNoAsistio(
    cita
) {

    cambiarEstado(
        cita,
        'NO_ASISTIO',
        'El paciente no asistió a la cita'
    );
}


/* =========================================================
   COLORES DE ESTADO
========================================================= */

function claseEstado(
    codigo
) {

    switch (codigo) {

        case 'PENDIENTE':

            return [
                'bg-amber-50',
                'text-amber-700',
            ];


        case 'CONFIRMADA':

            return [
                'bg-blue-50',
                'text-blue-700',
            ];


        case 'EN_ATENCION':

            return [
                'bg-violet-50',
                'text-violet-700',
            ];


        case 'ATENDIDA':

            return [
                'bg-emerald-50',
                'text-emerald-700',
            ];


        case 'CANCELADA':

            return [
                'bg-rose-50',
                'text-rose-700',
            ];


        case 'NO_ASISTIO':

            return [
                'bg-slate-100',
                'text-slate-600',
            ];


        case 'REPROGRAMADA':

            return [
                'bg-cyan-50',
                'text-cyan-700',
            ];


        default:

            return [
                'bg-slate-100',
                'text-slate-600',
            ];
    }
}


/* =========================================================
   FORMATEAR HORA
========================================================= */

function hora(
    valor
) {

    if (!valor) {
        return '';
    }


    return new Intl.DateTimeFormat(
        'es-PE',
        {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
            timeZone: 'America/Lima',
        }
    ).format(
        new Date(valor)
    );
}


/* =========================================================
   FORMATEAR FECHA
========================================================= */

function fechaBonita(
    valor
) {

    if (!valor) {
        return '';
    }


    return new Intl.DateTimeFormat(
        'es-PE',
        {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'America/Lima',
        }
    ).format(
        new Date(valor)
    );
}

function abrirDiaDesdeSemana(
    fechaSeleccionada
) {

    fecha.value =
        fechaSeleccionada;


    modoVista.value =
        'dia';
}

</script>


<template>

    <Head title="Citas" />


    <AppLayout
        titulo="Citas"
        descripcion="Agenda y atención de pacientes"
    >

        <!-- =====================================================
             HEADER
        ====================================================== -->

        <section
            class="
                flex
                flex-col
                gap-5
                sm:flex-row
                sm:items-center
                sm:justify-between
            "
        >

            <div
                class="
                    flex
                    items-center
                    gap-3
                "
            >

                <div
                    class="
                        flex
                        h-11
                        w-11
                        items-center
                        justify-center
                        rounded-xl
                        bg-clinica-50
                        text-clinica-700
                    "
                >
                    <CalendarDays
                        :size="21"
                    />
                </div>


                <div>

                    <h2
                        class="
                            text-xl
                            font-bold
                            text-slate-900
                        "
                    >
                        Agenda de citas
                    </h2>


                    <p
                        class="
                            mt-0.5
                            text-sm
                            text-slate-500
                        "
                    >
                        {{ citas.total }}
                        citas encontradas
                    </p>

                </div>

            </div>


    <div
        class="
            flex
            flex-wrap
            items-center
            gap-3
        "
    >

        <!-- MODOS -->

        <div
            class="
                inline-flex
                rounded-xl
                border
                border-slate-200
                bg-white
                p-1
            "
        >

            <button
                type="button"
                class="
                    rounded-lg
                    px-3
                    py-2
                    text-xs
                    font-semibold
                    transition
                "
                :class="
                    modoVista === 'lista'
                        ? [
                            'bg-slate-900',
                            'text-white',
                        ]
                        : [
                            'text-slate-500',
                            'hover:bg-slate-50',
                        ]
                "
                @click="
                    modoVista = 'lista'
                "
            >
                Lista
            </button>


            <button
                type="button"
                class="
                    rounded-lg
                    px-3
                    py-2
                    text-xs
                    font-semibold
                    transition
                "
                :class="
                    modoVista === 'dia'
                        ? [
                            'bg-slate-900',
                            'text-white',
                        ]
                        : [
                            'text-slate-500',
                            'hover:bg-slate-50',
                        ]
                "
                @click="
                    modoVista = 'dia'
                "
            >
                Día
            </button>


            <button
                type="button"
                class="
                    rounded-lg
                    px-3
                    py-2
                    text-xs
                    font-semibold
                    transition
                "
                :class="
                    modoVista === 'semana'
                        ? [
                            'bg-slate-900',
                            'text-white',
                        ]
                        : [
                            'text-slate-500',
                            'hover:bg-slate-50',
                        ]
                "
                @click="
                    modoVista = 'semana'
                "
            >
                Semana
            </button>

        </div>


        <!-- NUEVA CITA -->

        <button
            type="button"
            class="
                inline-flex
                items-center
                justify-center
                gap-2
                rounded-xl
                bg-clinica-700
                px-4
                py-2.5
                text-sm
                font-semibold
                text-white
                transition
                hover:bg-clinica-800
            "
            @click="nuevaCita"
        >

            <Plus :size="18" />

            Nueva cita

        </button>

    </div>

        </section>


        <!-- =====================================================
             FILTROS
        ====================================================== -->

        <section
            v-if="
                modoVista === 'lista'
            "
            class="
                mt-6
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-4
                shadow-sm
            "
        >

            <div
                class="
                    grid
                    gap-3
                    lg:grid-cols-[1fr_auto_auto_auto]
                "
            >

                <!-- BUSCAR -->

                <div class="relative">

                    <Search
                        :size="18"
                        class="
                            absolute
                            left-3.5
                            top-1/2
                            -translate-y-1/2
                            text-slate-400
                        "
                    />


                    <input
                        v-model="buscar"
                        type="search"
                        placeholder="Buscar paciente..."
                        class="
                            input-clinica
                            pl-10
                        "
                    >

                </div>


                <!-- FECHA -->

                <input
                    v-model="fecha"
                    type="date"
                    class="input-clinica"
                >


                <!-- PROFESIONAL -->

                <select
                    v-model="profesional"
                    class="input-clinica"
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


                <!-- ESTADO -->

                <select
                    v-model="estado"
                    class="input-clinica"
                >

                    <option value="">
                        Todos los estados
                    </option>


                    <option
                        v-for="
                            item
                            in estados
                        "
                        :key="item.id"
                        :value="item.id"
                    >
                        {{ item.nombre }}
                    </option>

                </select>

            </div>

        </section>


        <!-- =====================================================
             SIN CITAS
        ====================================================== -->

        <section
            v-if="
                modoVista === 'lista'
                &&
                !hayCitas
            "
                mt-5
                rounded-2xl
                border
                border-dashed
                border-slate-300
                bg-white
                px-6
                py-14
                text-center
            "
        >

            <CalendarDays
                :size="34"
                class="
                    mx-auto
                    text-slate-300
                "
            />


            <h3
                class="
                    mt-4
                    font-bold
                    text-slate-800
                "
            >
                No hay citas
            </h3>


            <p
                class="
                    mt-1
                    text-sm
                    text-slate-500
                "
            >
                No se encontraron citas con los filtros actuales.
            </p>

        </section>


        <!-- =====================================================
             LISTADO DE CITAS
        ====================================================== -->
         
        <AgendaDia
            v-if="
                modoVista === 'dia'
            "
            :profesionales="
                profesionales
            "
            :fecha-inicial="
                fecha
            "
            :profesional-id="
                profesional
            "
            @ver-cita="
                abrirDetalle
            "
        />

        <AgendaSemana
            v-if="
                modoVista === 'semana'
            "
            :profesionales="
                profesionales
            "
            :fecha-inicial="
                fecha
            "
            :profesional-id="
                profesional
            "
            @ver-cita="
                abrirDetalle
            "
            @ver-dia="
                abrirDiaDesdeSemana
            "
        />

        <section
            v-if="
                modoVista === 'lista'
                &&
                hayCitas
            "
            class="
                mt-5
                space-y-3
            "
        >

            <article
                v-for="
                    cita
                    in citas.data
                "
                :key="cita.id"
                class="
                    relative
                    overflow-visible
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    shadow-sm
                    transition
                    hover:border-slate-300
                    hover:shadow-md
                "
            >

                <div
                    class="
                        flex
                        flex-col
                        gap-5
                        xl:flex-row
                        xl:items-center
                    "
                >

                    <!-- =================================================
                         HORA
                    ================================================== -->

                    <div
                        class="
                            flex
                            min-w-40
                            items-center
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
                                rounded-xl
                                bg-clinica-50
                                text-clinica-700
                            "
                        >

                            <Clock3
                                :size="19"
                            />

                        </div>


                        <div>

                            <p
                                class="
                                    font-bold
                                    text-slate-900
                                "
                            >
                                {{
                                    hora(
                                        cita.fecha_hora_inicio
                                    )
                                }}
                            </p>


                            <p
                                class="
                                    text-xs
                                    text-slate-400
                                "
                            >
                                {{
                                    fechaBonita(
                                        cita.fecha_hora_inicio
                                    )
                                }}
                            </p>

                        </div>

                    </div>


                    <!-- =================================================
                         PACIENTE
                    ================================================== -->

                    <div
                        class="
                            min-w-0
                            flex-1
                        "
                    >

                        <div
                            class="
                                flex
                                items-center
                                gap-2
                            "
                        >

                            <UserRound
                                :size="16"
                                class="
                                    shrink-0
                                    text-slate-400
                                "
                            />


                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-bold
                                    text-slate-900
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

                        </div>


                        <p
                            class="
                                mt-1
                                truncate
                                text-xs
                                text-slate-500
                            "
                        >
                            {{
                                cita.motivo
                                ||
                                'Sin motivo especificado'
                            }}
                        </p>

                    </div>


                    <!-- =================================================
                         PROFESIONAL
                    ================================================== -->

                    <div
                        class="
                            min-w-48
                        "
                    >

                        <div
                            class="
                                flex
                                items-center
                                gap-2
                                text-sm
                                text-slate-600
                            "
                        >

                            <Stethoscope
                                :size="15"
                                class="shrink-0"
                            />


                            <span>
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

                    </div>


                    <!-- =================================================
                         CONSULTORIO
                    ================================================== -->

                    <div
                        class="
                            min-w-40
                        "
                    >

                        <div
                            class="
                                flex
                                items-center
                                gap-2
                                text-sm
                                text-slate-500
                            "
                        >

                            <DoorOpen
                                :size="15"
                                class="shrink-0"
                            />


                            <span>
                                {{
                                    cita.consultorio
                                        ?.nombre
                                    ||
                                    'Sin consultorio'
                                }}
                            </span>

                        </div>

                    </div>


                    <!-- =================================================
                         ESTADO
                    ================================================== -->

                    <div
                        class="
                            shrink-0
                        "
                    >

                        <span
                            class="
                                inline-flex
                                rounded-full
                                px-3
                                py-1.5
                                text-xs
                                font-semibold
                            "
                            :class="
                                claseEstado(
                                    codigoEstado(cita)
                                )
                            "
                        >
                            {{
                                cita.estado_cita
                                    ?.nombre
                                ??
                                'Sin estado'
                            }}
                        </span>

                    </div>


                    <!-- =================================================
                         ACCIONES
                    ================================================== -->

                    <div
                        class="
                            flex
                            shrink-0
                            flex-wrap
                            items-center
                            gap-2
                            xl:justify-end
                        "
                    >

                        <!-- =============================================
                             PENDIENTE
                        ============================================== -->

                        <button
                            v-if="
                                codigoEstado(cita)
                                ===
                                'PENDIENTE'
                            "
                            type="button"
                            :disabled="
                                cambiandoEstado
                                ===
                                cita.id
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-blue-600
                                px-3.5
                                py-2
                                text-xs
                                font-semibold
                                text-white
                                transition
                                hover:bg-blue-700
                                disabled:cursor-not-allowed
                                disabled:opacity-50
                            "
                            @click="
                                confirmarCita(cita)
                            "
                        >

                            <LoaderCircle
                                v-if="
                                    cambiandoEstado
                                    ===
                                    cita.id
                                "
                                :size="15"
                                class="animate-spin"
                            />


                            <CheckCircle2
                                v-else
                                :size="15"
                            />

                            Confirmar

                        </button>


                        <!-- =============================================
                             CONFIRMADA
                        ============================================== -->

                        <button
                            v-if="
                                codigoEstado(cita)
                                ===
                                'CONFIRMADA'
                            "
                            type="button"
                            :disabled="
                                cambiandoEstado
                                ===
                                cita.id
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-violet-600
                                px-3.5
                                py-2
                                text-xs
                                font-semibold
                                text-white
                                transition
                                hover:bg-violet-700
                                disabled:cursor-not-allowed
                                disabled:opacity-50
                            "
                            @click="
                                iniciarAtencion(cita)
                            "
                        >

                            <LoaderCircle
                                v-if="
                                    cambiandoEstado
                                    ===
                                    cita.id
                                "
                                :size="15"
                                class="animate-spin"
                            />


                            <Play
                                v-else
                                :size="15"
                            />

                            Iniciar atención

                        </button>


                        <!-- =============================================
                             EN ATENCIÓN
                        ============================================== -->

                        <button
                            v-if="
                                codigoEstado(cita)
                                ===
                                'EN_ATENCION'
                            "
                            type="button"
                            :disabled="
                                cambiandoEstado
                                ===
                                cita.id
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-emerald-600
                                px-3.5
                                py-2
                                text-xs
                                font-semibold
                                text-white
                                transition
                                hover:bg-emerald-700
                                disabled:cursor-not-allowed
                                disabled:opacity-50
                            "
                            @click="
                                finalizarAtencion(cita)
                            "
                        >

                            <LoaderCircle
                                v-if="
                                    cambiandoEstado
                                    ===
                                    cita.id
                                "
                                :size="15"
                                class="animate-spin"
                            />


                            <Flag
                                v-else
                                :size="15"
                            />

                            Finalizar atención

                        </button>


                        <!-- =============================================
                             ATENDIDA
                        ============================================== -->

                        <div
                            v-if="
                                codigoEstado(cita)
                                ===
                                'ATENDIDA'
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-emerald-50
                                px-3
                                py-2
                                text-xs
                                font-semibold
                                text-emerald-700
                            "
                        >

                            <CheckCircle2
                                :size="15"
                            />

                            Finalizada

                        </div>


                        <!-- =============================================
                             MENÚ
                        ============================================== -->

                        <details
                            class="
                                group
                                relative
                            "
                        >

                            <summary
                                class="
                                    flex
                                    h-9
                                    w-9
                                    cursor-pointer
                                    list-none
                                    items-center
                                    justify-center
                                    rounded-xl
                                    border
                                    border-slate-200
                                    bg-white
                                    text-slate-500
                                    transition
                                    hover:border-slate-300
                                    hover:bg-slate-50
                                    hover:text-slate-800
                                    [&::-webkit-details-marker]:hidden
                                "
                            >

                                <EllipsisVertical
                                    :size="18"
                                />

                            </summary>


                            <div
                                class="
                                    absolute
                                    right-0
                                    top-11
                                    z-50
                                    w-52
                                    overflow-hidden
                                    rounded-xl
                                    border
                                    border-slate-200
                                    bg-white
                                    p-1.5
                                    shadow-xl
                                "
                            >

                                <!-- VER DETALLE -->

                                <button
                                    type="button"
                                    class="
                                        flex
                                        w-full
                                        items-center
                                        gap-3
                                        rounded-lg
                                        px-3
                                        py-2.5
                                        text-left
                                        text-sm
                                        font-medium
                                        text-slate-700
                                        transition
                                        hover:bg-slate-50
                                    "
                                    @click="
                                        abrirDetalle(cita)
                                    "
                                >

                                    <Eye
                                        :size="16"
                                        class="
                                            text-slate-400
                                        "
                                    />

                                    Ver detalle

                                </button>


                                <!-- EDITAR -->

                                <button
                                    v-if="
                                        [
                                            'PENDIENTE',
                                            'CONFIRMADA'
                                        ].includes(
                                            codigoEstado(cita)
                                        )
                                    "
                                    type="button"
                                    class="
                                        flex
                                        w-full
                                        items-center
                                        gap-3
                                        rounded-lg
                                        px-3
                                        py-2.5
                                        text-left
                                        text-sm
                                        font-medium
                                        text-slate-700
                                        transition
                                        hover:bg-slate-50
                                    "
                                    @click="
                                        editarCita(cita)
                                    "
                                >

                                    <Pencil
                                        :size="16"
                                        class="
                                            text-slate-400
                                        "
                                    />

                                    Editar cita

                                </button>


                                <!-- REPROGRAMAR -->

                                <button
                                    v-if="
                                        [
                                            'PENDIENTE',
                                            'CONFIRMADA'
                                        ].includes(
                                            codigoEstado(cita)
                                        )
                                    "
                                    type="button"
                                    class="
                                        flex
                                        w-full
                                        items-center
                                        gap-3
                                        rounded-lg
                                        px-3
                                        py-2.5
                                        text-left
                                        text-sm
                                        font-medium
                                        text-blue-700
                                        transition
                                        hover:bg-blue-50
                                    "
                                    @click="
                                        abrirReprogramar(cita)
                                    "
                                >

                                    <CalendarClock
                                        :size="16"
                                    />

                                    Reprogramar

                                </button>


                                <!-- SEPARADOR -->

                                <div
                                    v-if="
                                        [
                                            'PENDIENTE',
                                            'CONFIRMADA'
                                        ].includes(
                                            codigoEstado(cita)
                                        )
                                    "
                                    class="
                                        my-1
                                        border-t
                                        border-slate-100
                                    "
                                />


                                <!-- NO ASISTIÓ -->

                                <button
                                    v-if="
                                        [
                                            'PENDIENTE',
                                            'CONFIRMADA'
                                        ].includes(
                                            codigoEstado(cita)
                                        )
                                    "
                                    type="button"
                                    :disabled="
                                        cambiandoEstado
                                        ===
                                        cita.id
                                    "
                                    class="
                                        flex
                                        w-full
                                        items-center
                                        gap-3
                                        rounded-lg
                                        px-3
                                        py-2.5
                                        text-left
                                        text-sm
                                        font-medium
                                        text-slate-600
                                        transition
                                        hover:bg-slate-100
                                        disabled:opacity-50
                                    "
                                    @click="
                                        marcarNoAsistio(cita)
                                    "
                                >

                                    <UserRound
                                        :size="16"
                                    />

                                    No asistió

                                </button>


                                <!-- CANCELAR -->

                                <button
                                    v-if="
                                        [
                                            'PENDIENTE',
                                            'CONFIRMADA'
                                        ].includes(
                                            codigoEstado(cita)
                                        )
                                    "
                                    type="button"
                                    class="
                                        flex
                                        w-full
                                        items-center
                                        gap-3
                                        rounded-lg
                                        px-3
                                        py-2.5
                                        text-left
                                        text-sm
                                        font-medium
                                        text-rose-600
                                        transition
                                        hover:bg-rose-50
                                    "
                                    @click="
                                        abrirCancelar(cita)
                                    "
                                >

                                    <CalendarX
                                        :size="16"
                                    />

                                    Cancelar cita

                                </button>

                            </div>

                        </details>

                    </div>

                </div>

            </article>

        </section>


        <!-- =====================================================
             PAGINACIÓN
        ====================================================== -->

        <div
            v-if="
                modoVista === 'lista'
                &&
                citas.links
                &&
                citas.links.length > 3
            "
            class="
                mt-6
                flex
                flex-wrap
                justify-center
                gap-2
            "
        >

            <template
                v-for="
                    link
                    in citas.links
                "
                :key="link.label"
            >

                <span
                    v-if="!link.url"
                    class="
                        rounded-lg
                        border
                        border-slate-200
                        px-3
                        py-2
                        text-sm
                        text-slate-300
                    "
                    v-html="link.label"
                />


                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="
                        rounded-lg
                        border
                        px-3
                        py-2
                        text-sm
                        font-medium
                        transition
                    "
                    :class="
                        link.active
                            ? [
                                'border-clinica-600',
                                'bg-clinica-600',
                                'text-white',
                            ]
                            : [
                                'border-slate-200',
                                'bg-white',
                                'text-slate-600',
                                'hover:bg-slate-50',
                            ]
                    "
                    v-html="link.label"
                />

            </template>

        </div>


        <!-- =====================================================
             COMPONENTES
        ====================================================== -->

        <CitaDetalleDrawer
            :open="detalleAbierto"
            :cita="citaDetalle"
            @close="cerrarDetalle"
        />


        <CitaFormDrawer
            :open="drawerAbierto"
            :cita="citaSeleccionada"
            :profesionales="profesionales"
            :consultorios="consultorios"
            :estados="estados"
            :estado-pendiente="estadoPendiente"
            @close="cerrarDrawer"
        />


        <CancelarCitaModal
            :open="cancelarModalAbierto"
            :cita="citaCancelar"
            @close="cerrarCancelar"
        />


        <ReprogramarCitaModal
            :open="reprogramarModalAbierto"
            :cita="citaReprogramar"
            :profesionales="profesionales"
            :consultorios="consultorios"
            @close="cerrarReprogramar"
        />


        <FinalizarAtencionModal
            :open="finalizarModalAbierto"
            :cita="citaFinalizar"
            :profesionales="profesionales"
            :consultorios="consultorios"
            @close="cerrarFinalizarAtencion"
            @success="cerrarFinalizarAtencion"
        />

    </AppLayout>

</template>