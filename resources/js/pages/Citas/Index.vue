<script setup>

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
} from 'lucide-vue-next';

import AppLayout
    from '@/layouts/AppLayout.vue';

import CitaFormDrawer
    from '@/components/citas/CitaFormDrawer.vue';

import CancelarCitaModal
    from '@/components/citas/CancelarCitaModal.vue';


/* =========================================================
   PROPS
========================================================= */

const props = defineProps({

    citas: {
        type: Object,
        required: true,
    },

    pacientes: {
        type: Array,
        default: () => [],
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
   DRAWER DE CITA
========================================================= */

const drawerAbierto =
    ref(false);

const citaSeleccionada =
    ref(null);


/* =========================================================
   MODAL DE CANCELACIÓN
========================================================= */

const cancelarModalAbierto =
    ref(false);

const citaCancelar =
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
   WATCHERS DE FILTROS
========================================================= */

watch(
    buscar,
    () => {

        clearTimeout(
            temporizador
        );

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
    aplicarFiltros
);


/* =========================================================
   FILTRAR CITAS
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
   EDITAR CITA
========================================================= */

function editarCita(
    cita
) {

    citaSeleccionada.value =
        cita;

    drawerAbierto.value =
        true;
}


/* =========================================================
   CERRAR DRAWER
========================================================= */

function cerrarDrawer() {

    drawerAbierto.value =
        false;

    citaSeleccionada.value =
        null;
}


/* =========================================================
   ABRIR MODAL DE CANCELACIÓN
========================================================= */

function abrirCancelar(
    cita
) {

    citaCancelar.value =
        cita;

    cancelarModalAbierto.value =
        true;
}


/* =========================================================
   CERRAR MODAL DE CANCELACIÓN
========================================================= */

function cerrarCancelar() {

    cancelarModalAbierto.value =
        false;

    citaCancelar.value =
        null;
}


/* =========================================================
   OBTENER ESTADO POR CÓDIGO
========================================================= */

function obtenerEstado(
    codigo
) {

    return props.estados.find(
        estado =>
            estado.codigo === codigo
    );
}


/* =========================================================
   OBTENER CÓDIGO DE ESTADO DE UNA CITA
========================================================= */

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

    if (
        !cita?.id
    ) {

        console.error(
            'La cita no tiene un ID válido.'
        );

        return;
    }


    const nuevoEstado =
        obtenerEstado(
            codigo
        );


    if (
        !nuevoEstado
    ) {

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
   CONFIRMAR CITA
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

function finalizarAtencion(
    cita
) {

    cambiarEstado(
        cita,
        'ATENDIDA',
        'Atención finalizada'
    );
}


/* =========================================================
   CLASE VISUAL DEL ESTADO
========================================================= */

function claseEstado(
    codigo
) {

    switch (
        codigo
    ) {

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

</script>


<template>

    <Head title="Citas" />


    <AppLayout
        titulo="Citas"
        descripcion="Agenda y atención de pacientes"
    >

        <!-- HEADER -->

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

            <div class="flex items-center gap-3">

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
                    <CalendarDays :size="21" />
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
                "
                @click="nuevaCita"
            >
                <Plus :size="18" />

                Nueva cita
            </button>

        </section>


        <!-- FILTROS -->

        <section
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


                <input
                    v-model="fecha"
                    type="date"
                    class="input-clinica"
                >


                <select
                    v-model="profesional"
                    class="input-clinica"
                >
                    <option value="">
                        Todos los profesionales
                    </option>

                    <option
                        v-for="item in profesionales"
                        :key="item.id"
                        :value="item.id"
                    >
                        {{ item.nombres }}
                        {{ item.apellidos }}
                    </option>
                </select>


                <select
                    v-model="estado"
                    class="input-clinica"
                >
                    <option value="">
                        Todos los estados
                    </option>

                    <option
                        v-for="item in estados"
                        :key="item.id"
                        :value="item.id"
                    >
                        {{ item.nombre }}
                    </option>
                </select>

            </div>

        </section>


        <!-- CITAS -->

        <section
            class="
                mt-5
                space-y-3
            "
        >

            <article
                v-for="cita in citas.data"
                :key="cita.id"
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    shadow-sm
                    transition
                    hover:shadow-md
                "
            >

                <div
                    class="
                        flex
                        flex-col
                        gap-5
                        lg:flex-row
                        lg:items-center
                    "
                >

                    <!-- HORA -->

                    <div
                        class="
                            flex
                            min-w-36
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
                            <Clock3 :size="19" />
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


                    <!-- PACIENTE -->

                    <div class="min-w-0 flex-1">

                        <div
                            class="
                                flex
                                items-center
                                gap-2
                            "
                        >
                            <UserRound
                                :size="16"
                                class="text-slate-400"
                            />

                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-bold
                                    text-slate-900
                                "
                            >
                                {{ cita.paciente.nombres }}
                                {{ cita.paciente.apellidos }}
                            </p>
                        </div>

                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-500
                            "
                        >
                            {{ cita.motivo || 'Sin motivo especificado' }}
                        </p>

                    </div>


                    <!-- PROFESIONAL -->

                    <div class="min-w-48">

                        <div
                            class="
                                flex
                                items-center
                                gap-2
                                text-sm
                                text-slate-600
                            "
                        >
                            <Stethoscope :size="15" />

                            {{ cita.profesional.nombres }}
                            {{ cita.profesional.apellidos }}
                        </div>

                    </div>


                    <!-- CONSULTORIO -->

                    <div class="min-w-40">

                        <div
                            class="
                                flex
                                items-center
                                gap-2
                                text-sm
                                text-slate-500
                            "
                        >
                            <DoorOpen :size="15" />

                            {{
                                cita.consultorio?.nombre ||
                                'Sin consultorio'
                            }}
                        </div>

                    </div>


                    <!-- ESTADO -->

                    <span
                        class="
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
                        {{ cita.estado_cita.nombre }}
                    </span>


                    <div
                        class="
                            flex
                            flex-wrap
                            items-center
                            justify-end
                            gap-2
                        "
                    >

                        <!-- PENDIENTE -->

                        <button
                            v-if="
                                codigoEstado(cita) ===
                                'PENDIENTE'
                            "
                            type="button"
                            :disabled="
                                cambiandoEstado ===
                                cita.id
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-blue-600
                                px-3
                                py-2
                                text-xs
                                font-semibold
                                text-white
                                transition
                                hover:bg-blue-700
                                disabled:opacity-50
                            "
                            @click="
                                confirmarCita(cita)
                            "
                        >

                            <LoaderCircle
                                v-if="
                                    cambiandoEstado ===
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


                        <!-- CONFIRMADA -->

                        <button
                            v-if="
                                codigoEstado(cita) ===
                                'CONFIRMADA'
                            "
                            type="button"
                            :disabled="
                                cambiandoEstado ===
                                cita.id
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-violet-600
                                px-3
                                py-2
                                text-xs
                                font-semibold
                                text-white
                                transition
                                hover:bg-violet-700
                                disabled:opacity-50
                            "
                            @click="
                                iniciarAtencion(cita)
                            "
                        >

                            <Play
                                :size="15"
                            />

                            Iniciar atención

                        </button>


                        <!-- EN ATENCIÓN -->

                        <button
                            v-if="
                                codigoEstado(cita) ===
                                'EN_ATENCION'
                            "
                            type="button"
                            :disabled="
                                cambiandoEstado ===
                                cita.id
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-emerald-600
                                px-3
                                py-2
                                text-xs
                                font-semibold
                                text-white
                                transition
                                hover:bg-emerald-700
                                disabled:opacity-50
                            "
                            @click="
                                finalizarAtencion(cita)
                            "
                        >

                            <Flag
                                :size="15"
                            />

                            Finalizar atención

                        </button>


                        <!-- ATENDIDA -->

                        <div
                            v-if="
                                codigoEstado(cita) ===
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

                            Atención finalizada

                        </div>


                        <!-- EDITAR -->

                        <button
                            v-if="
                                ![
                                    'CANCELADA',
                                    'ATENDIDA'
                                ].includes(
                                    codigoEstado(cita)
                                )
                            "
                            type="button"
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                px-3
                                py-2
                                text-xs
                                font-semibold
                                text-slate-600
                                transition
                                hover:bg-slate-50
                            "
                            @click="
                                editarCita(cita)
                            "
                        >

                            <Pencil :size="14" />

                            Editar

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
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                border
                                border-rose-200
                                bg-white
                                px-3
                                py-2
                                text-xs
                                font-semibold
                                text-rose-600
                                transition
                                hover:bg-rose-50
                            "
                            @click="
                                abrirCancelar(cita)
                            "
                        >

                            <CalendarX :size="14" />

                            Cancelar

                        </button>

                    </div>

                </div>

            </article>

        </section>


        <!-- VACÍO -->

        <section
            v-if="!hayCitas"
            class="
                mt-5
                flex
                min-h-72
                flex-col
                items-center
                justify-center
                rounded-2xl
                border
                border-slate-200
                bg-white
                text-center
            "
        >

            <CalendarDays
                :size="32"
                class="text-slate-300"
            />

            <h3
                class="
                    mt-4
                    font-semibold
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
                Programa una nueva cita para comenzar.
            </p>

        </section>

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

    </AppLayout>

</template>