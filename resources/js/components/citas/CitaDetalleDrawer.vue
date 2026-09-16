<script setup>

import {
    computed,
} from 'vue';

import {
    X,
    UserRound,
    Stethoscope,
    DoorOpen,
    Clock3,
    CalendarDays,
    History,
    ReceiptText,
    RefreshCcw,
    CheckCircle2,
} from 'lucide-vue-next';


const props = defineProps({

    open: {
        type: Boolean,
        default: false,
    },

    cita: {
        type: Object,
        default: null,
    },

});


const emit = defineEmits([
    'close',
]);


/* =========================================================
   SERVICIO
========================================================= */

const servicioCita = computed(() => {

    return props.cita
        ?.servicios_cita
        ?.[0]
        ?? null;

});


const servicio = computed(() => {

    return servicioCita.value
        ?.servicio
        ?? null;

});


/* =========================================================
   PRECIO
========================================================= */

function precio(
    valor
) {

    if (
        valor === null ||
        valor === undefined
    ) {
        return '—';
    }


    return new Intl.NumberFormat(
        'es-PE',
        {
            style: 'currency',
            currency: 'PEN',
        }
    ).format(
        Number(valor)
    );
}


/* =========================================================
   FECHA
========================================================= */

function fechaBonita(
    valor
) {

    if (!valor) {
        return '—';
    }


    return new Intl.DateTimeFormat(
        'es-PE',
        {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            timeZone: 'America/Lima',
        }
    ).format(
        new Date(valor)
    );
}


/* =========================================================
   HORA
========================================================= */

function hora(
    valor
) {

    if (!valor) {
        return '—';
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
   FECHA + HORA
========================================================= */

function fechaHora(
    valor
) {

    if (!valor) {
        return '—';
    }


    return new Intl.DateTimeFormat(
        'es-PE',
        {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
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
   COLOR ESTADO
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
   CERRAR
========================================================= */

function cerrar() {

    emit('close');
}

</script>


<template>

    <div>

        <!-- FONDO -->

        <Transition
            enter-active-class="
                transition-opacity
                duration-200
            "
            leave-active-class="
                transition-opacity
                duration-150
            "
            enter-from-class="
                opacity-0
            "
            leave-to-class="
                opacity-0
            "
        >

            <div
                v-if="open"
                class="
                    fixed
                    inset-0
                    z-[150]
                    bg-slate-950/35
                    backdrop-blur-[2px]
                "
                @click="cerrar"
            />

        </Transition>


        <!-- DRAWER -->

        <Transition
            enter-active-class="
                transition-transform
                duration-300
                ease-out
            "
            leave-active-class="
                transition-transform
                duration-200
                ease-in
            "
            enter-from-class="
                translate-x-full
            "
            leave-to-class="
                translate-x-full
            "
        >

            <aside
                v-if="open"
                class="
                    fixed
                    right-0
                    top-0
                    z-[160]
                    flex
                    h-screen
                    w-full
                    max-w-xl
                    flex-col
                    border-l
                    border-slate-200
                    bg-white
                    shadow-2xl
                "
            >

                <!-- HEADER -->

                <header
                    class="
                        flex
                        shrink-0
                        items-start
                        justify-between
                        gap-4
                        border-b
                        border-slate-100
                        px-6
                        py-5
                    "
                >

                    <div>

                        <p
                            class="
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wider
                                text-clinica-600
                            "
                        >
                            Cita #{{ cita?.id }}
                        </p>


                        <h2
                            class="
                                mt-1
                                text-xl
                                font-bold
                                tracking-tight
                                text-slate-900
                            "
                        >
                            Detalle de la cita
                        </h2>


                        <p
                            class="
                                mt-1
                                text-sm
                                text-slate-500
                            "
                        >
                            Información e historial de atención.
                        </p>

                    </div>


                    <button
                        type="button"
                        class="
                            flex
                            h-10
                            w-10
                            shrink-0
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
                        <X :size="20" />
                    </button>

                </header>


                <!-- CONTENIDO -->

                <div
                    class="
                        flex-1
                        overflow-y-auto
                        px-6
                        py-6
                    "
                >

                    <div
                        v-if="cita"
                        class="
                            space-y-6
                        "
                    >

                        <!-- ESTADO -->

                        <section>

                            <span
                                class="
                                    inline-flex
                                    items-center
                                    rounded-full
                                    px-3
                                    py-1.5
                                    text-xs
                                    font-bold
                                "
                                :class="
                                    claseEstado(
                                        cita.estado_cita
                                            ?.codigo
                                    )
                                "
                            >
                                {{
                                    cita.estado_cita
                                        ?.nombre
                                    ?? 'Sin estado'
                                }}
                            </span>

                        </section>


                        <!-- PACIENTE -->

                        <section
                            class="
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                p-5
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
                                        h-10
                                        w-10
                                        items-center
                                        justify-center
                                        rounded-xl
                                        bg-clinica-50
                                        text-clinica-700
                                    "
                                >
                                    <UserRound
                                        :size="19"
                                    />
                                </div>


                                <div>

                                    <p
                                        class="
                                            text-xs
                                            font-semibold
                                            uppercase
                                            tracking-wide
                                            text-slate-400
                                        "
                                    >
                                        Paciente
                                    </p>

                                    <p
                                        class="
                                            mt-0.5
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

                            </div>


                            <div
                                class="
                                    mt-4
                                    grid
                                    gap-3
                                    sm:grid-cols-2
                                "
                            >

                                <div>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-400
                                        "
                                    >
                                        Código
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        {{
                                            cita.paciente
                                                ?.codigo
                                            ?? '—'
                                        }}
                                    </p>

                                </div>


                                <div>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-400
                                        "
                                    >
                                        Documento
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        {{
                                            cita.paciente
                                                ?.numero_documento
                                            ?? '—'
                                        }}
                                    </p>

                                </div>

                            </div>

                        </section>


                        <!-- SERVICIO -->

                        <section
                            class="
                                rounded-2xl
                                border
                                border-slate-200
                                p-5
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
                                        h-10
                                        w-10
                                        items-center
                                        justify-center
                                        rounded-xl
                                        bg-blue-50
                                        text-blue-600
                                    "
                                >
                                    <ReceiptText
                                        :size="19"
                                    />
                                </div>


                                <div>

                                    <p
                                        class="
                                            text-xs
                                            font-semibold
                                            uppercase
                                            tracking-wide
                                            text-slate-400
                                        "
                                    >
                                        Servicio
                                    </p>

                                    <p
                                        class="
                                            mt-0.5
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        {{
                                            servicio?.nombre
                                            ?? 'Sin servicio'
                                        }}
                                    </p>

                                </div>

                            </div>


                            <div
                                class="
                                    mt-4
                                    grid
                                    grid-cols-2
                                    gap-4
                                "
                            >

                                <div>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-400
                                        "
                                    >
                                        Duración
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        {{
                                            servicio
                                                ?.duracion_estimada_minutos
                                            ?? '—'
                                        }}
                                        min
                                    </p>

                                </div>


                                <div>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-400
                                        "
                                    >
                                        Precio registrado
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        {{
                                            precio(
                                                servicioCita
                                                    ?.precio_unitario
                                            )
                                        }}
                                    </p>

                                </div>

                            </div>

                        </section>


                        <!-- PROFESIONAL / CONSULTORIO -->

                        <section
                            class="
                                grid
                                gap-4
                                sm:grid-cols-2
                            "
                        >

                            <div
                                class="
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    p-5
                                "
                            >

                                <Stethoscope
                                    :size="20"
                                    class="
                                        text-violet-600
                                    "
                                />


                                <p
                                    class="
                                        mt-3
                                        text-xs
                                        text-slate-400
                                    "
                                >
                                    Profesional
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        font-bold
                                        text-slate-800
                                    "
                                >
                                    {{
                                        cita.profesional
                                            ?.nombres
                                    }}

                                    {{
                                        cita.profesional
                                            ?.apellidos
                                    }}
                                </p>

                            </div>


                            <div
                                class="
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    p-5
                                "
                            >

                                <DoorOpen
                                    :size="20"
                                    class="
                                        text-cyan-600
                                    "
                                />


                                <p
                                    class="
                                        mt-3
                                        text-xs
                                        text-slate-400
                                    "
                                >
                                    Consultorio
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        font-bold
                                        text-slate-800
                                    "
                                >
                                    {{
                                        cita.consultorio
                                            ?.nombre
                                        ?? 'Sin consultorio'
                                    }}
                                </p>

                            </div>

                        </section>


                        <!-- HORARIO -->

                        <section
                            class="
                                rounded-2xl
                                border
                                border-slate-200
                                p-5
                            "
                        >

                            <div
                                class="
                                    flex
                                    items-center
                                    gap-2
                                    text-slate-700
                                "
                            >
                                <CalendarDays
                                    :size="18"
                                />

                                <p
                                    class="
                                        font-bold
                                    "
                                >
                                    Horario
                                </p>
                            </div>


                            <div
                                class="
                                    mt-4
                                    grid
                                    gap-4
                                    sm:grid-cols-2
                                "
                            >

                                <div>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-400
                                        "
                                    >
                                        Fecha
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-semibold
                                            text-slate-800
                                        "
                                    >
                                        {{
                                            fechaBonita(
                                                cita.fecha_hora_inicio
                                            )
                                        }}
                                    </p>

                                </div>


                                <div>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-400
                                        "
                                    >
                                        Hora estimada
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-semibold
                                            text-slate-800
                                        "
                                    >
                                        {{
                                            hora(
                                                cita.fecha_hora_inicio
                                            )
                                        }}

                                        —

                                        {{
                                            hora(
                                                cita.fecha_hora_fin
                                            )
                                        }}
                                    </p>

                                </div>

                            </div>


                            <div
                                v-if="
                                    cita.fecha_hora_fin_real
                                "
                                class="
                                    mt-4
                                    rounded-xl
                                    bg-emerald-50
                                    p-3
                                "
                            >

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                        text-sm
                                        font-semibold
                                        text-emerald-700
                                    "
                                >
                                    <CheckCircle2
                                        :size="16"
                                    />

                                    Finalización real:

                                    {{
                                        hora(
                                            cita.fecha_hora_fin_real
                                        )
                                    }}
                                </div>

                            </div>

                        </section>


                        <!-- REPROGRAMACIÓN -->

                        <section
                            v-if="
                                cita.cita_origen ||
                                (
                                    cita.citas_reprogramadas
                                    &&
                                    cita.citas_reprogramadas.length
                                )
                            "
                            class="
                                rounded-2xl
                                border
                                border-cyan-100
                                bg-cyan-50/60
                                p-5
                            "
                        >

                            <div
                                class="
                                    flex
                                    items-center
                                    gap-2
                                    font-bold
                                    text-cyan-800
                                "
                            >

                                <RefreshCcw
                                    :size="18"
                                />

                                Reprogramación

                            </div>


                            <div
                                v-if="
                                    cita.cita_origen
                                "
                                class="
                                    mt-4
                                "
                            >

                                <p
                                    class="
                                        text-xs
                                        text-cyan-600
                                    "
                                >
                                    Esta cita proviene de:
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        font-semibold
                                        text-cyan-900
                                    "
                                >
                                    Cita
                                    #{{ cita.cita_origen.id }}

                                    ·

                                    {{
                                        fechaHora(
                                            cita.cita_origen
                                                .fecha_hora_inicio
                                        )
                                    }}
                                </p>

                            </div>


                            <div
                                v-if="
                                    cita.citas_reprogramadas
                                        ?.length
                                "
                                class="
                                    mt-4
                                "
                            >

                                <p
                                    class="
                                        text-xs
                                        text-cyan-600
                                    "
                                >
                                    Se reprogramó como:
                                </p>


                                <div
                                    v-for="
                                        nueva
                                        in cita.citas_reprogramadas
                                    "
                                    :key="nueva.id"
                                    class="
                                        mt-1
                                        text-sm
                                        font-semibold
                                        text-cyan-900
                                    "
                                >
                                    Cita
                                    #{{ nueva.id }}

                                    ·

                                    {{
                                        fechaHora(
                                            nueva.fecha_hora_inicio
                                        )
                                    }}
                                </div>

                            </div>

                        </section>


                        <!-- CANCELACIÓN -->

                        <section
                            v-if="
                                cita.fecha_cancelacion
                            "
                            class="
                                rounded-2xl
                                border
                                border-rose-100
                                bg-rose-50
                                p-5
                            "
                        >

                            <p
                                class="
                                    text-sm
                                    font-bold
                                    text-rose-700
                                "
                            >
                                Cita cancelada
                            </p>


                            <p
                                class="
                                    mt-2
                                    text-xs
                                    text-rose-600
                                "
                            >
                                {{
                                    fechaHora(
                                        cita.fecha_cancelacion
                                    )
                                }}
                            </p>


                            <p
                                v-if="
                                    cita.motivo_cancelacion
                                "
                                class="
                                    mt-3
                                    text-sm
                                    leading-6
                                    text-rose-800
                                "
                            >
                                {{
                                    cita.motivo_cancelacion
                                }}
                            </p>

                        </section>


                        <!-- HISTORIAL -->

                        <section>

                            <div
                                class="
                                    mb-4
                                    flex
                                    items-center
                                    gap-2
                                "
                            >

                                <History
                                    :size="19"
                                    class="
                                        text-slate-500
                                    "
                                />


                                <h3
                                    class="
                                        font-bold
                                        text-slate-900
                                    "
                                >
                                    Historial
                                </h3>

                            </div>


                            <div
                                v-if="
                                    cita.historial_estados
                                        ?.length
                                "
                                class="
                                    space-y-0
                                "
                            >

                                <div
                                    v-for="
                                        (
                                            item,
                                            indice
                                        )
                                        in cita.historial_estados
                                    "
                                    :key="item.id"
                                    class="
                                        relative
                                        flex
                                        gap-4
                                        pb-6
                                    "
                                >

                                    <!-- LÍNEA -->

                                    <div
                                        v-if="
                                            indice <
                                            cita.historial_estados
                                                .length - 1
                                        "
                                        class="
                                            absolute
                                            left-[7px]
                                            top-4
                                            h-full
                                            w-px
                                            bg-slate-200
                                        "
                                    />


                                    <!-- PUNTO -->

                                    <div
                                        class="
                                            relative
                                            z-10
                                            mt-1
                                            h-4
                                            w-4
                                            shrink-0
                                            rounded-full
                                            border-4
                                            border-white
                                            bg-clinica-600
                                            shadow-sm
                                        "
                                    />


                                    <!-- INFO -->

                                    <div
                                        class="
                                            min-w-0
                                            flex-1
                                        "
                                    >

                                        <div
                                            class="
                                                flex
                                                flex-wrap
                                                items-center
                                                justify-between
                                                gap-2
                                            "
                                        >

                                            <p
                                                class="
                                                    text-sm
                                                    font-bold
                                                    text-slate-800
                                                "
                                            >
                                                {{
                                                    item.estado_nuevo
                                                        ?.nombre
                                                    ?? 'Cambio de estado'
                                                }}
                                            </p>


                                            <span
                                                class="
                                                    text-xs
                                                    text-slate-400
                                                "
                                            >
                                                {{
                                                    fechaHora(
                                                        item.fecha_cambio
                                                    )
                                                }}
                                            </span>

                                        </div>


                                        <p
                                            v-if="
                                                item.motivo
                                            "
                                            class="
                                                mt-1
                                                text-sm
                                                leading-5
                                                text-slate-500
                                            "
                                        >
                                            {{ item.motivo }}
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div
                                v-else
                                class="
                                    rounded-xl
                                    bg-slate-50
                                    p-4
                                    text-sm
                                    text-slate-500
                                "
                            >
                                Esta cita todavía no tiene
                                movimientos registrados.
                            </div>

                        </section>

                    </div>

                </div>

            </aside>

        </Transition>

    </div>

</template>