<script setup>

import {
    ref,
    watch,
} from 'vue';

import {
    Head,
    router,
} from '@inertiajs/vue3';

import {
    Plus,
    Search,
    WalletCards,
    Banknote,
    MoreVertical,
    Ban,
    ReceiptText,
} from 'lucide-vue-next';

import AppLayout
    from '@/layouts/AppLayout.vue';

import PagoFormDrawer
    from '@/components/pagos/PagoFormDrawer.vue';

import AnularPagoModal
    from '@/components/pagos/AnularPagoModal.vue';


const props = defineProps({

    pagos: {
        type: Object,
        required: true,
    },

    metodosPago: {
        type: Array,
        default: () => [],
    },

    filtros: {
        type: Object,
        default: () => ({}),
    },

});


const buscar =
    ref(
        props.filtros.buscar
        ??
        ''
    );

const estado =
    ref(
        props.filtros.estado
        ??
        ''
    );


const drawerAbierto =
    ref(false);

const modalAnular =
    ref(false);

const pagoSeleccionado =
    ref(null);

let temporizador =
    null;


/* =========================================================
   FILTROS
========================================================= */

function aplicarFiltros() {

    router.get(
        '/clinica/pagos',
        {
            buscar:
                buscar.value,

            estado:
                estado.value,
        },
        {
            preserveState:
                true,

            preserveScroll:
                true,

            replace:
                true,
        }
    );
}


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
    estado,
    aplicarFiltros
);


/* =========================================================
   DINERO
========================================================= */

function dinero(
    valor
) {

    return new Intl.NumberFormat(
        'es-PE',
        {
            style: 'currency',
            currency: 'PEN',
        }
    ).format(
        Number(valor ?? 0)
    );
}


/* =========================================================
   FECHA
========================================================= */

function fechaPago(
    valor
) {

    if (!valor) {
        return '—';
    }


    const coincidencia =
        String(valor).match(
            /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/
        );


    if (!coincidencia) {
        return valor;
    }


    return (
        `${coincidencia[3]}/`
        +
        `${coincidencia[2]}/`
        +
        `${coincidencia[1]} `
        +
        `${coincidencia[4]}:`
        +
        `${coincidencia[5]}`
    );
}


/* =========================================================
   SERVICIO
========================================================= */

function servicio(
    pago
) {

    return (
        pago
            ?.cita
            ?.servicios_cita
            ?.[0]
            ?.servicio
            ?.nombre
        ??
        'Servicio odontológico'
    );
}


/* =========================================================
   ANULAR
========================================================= */

function abrirAnular(
    pago
) {

    pagoSeleccionado.value =
        pago;


    modalAnular.value =
        true;
}

</script>


<template>

    <Head title="Pagos" />


    <AppLayout>

        <div
            class="
                mx-auto
                max-w-7xl
                p-5
                lg:p-8
            "
        >

            <!-- HEADER -->

            <div
                class="
                    flex
                    flex-col
                    gap-4
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                "
            >

                <div>

                    <div
                        class="
                            flex
                            items-center
                            gap-2
                        "
                    >

                        <WalletCards
                            :size="23"
                            class="text-clinica-600"
                        />


                        <h1
                            class="
                                text-2xl
                                font-bold
                                text-slate-900
                            "
                        >
                            Pagos
                        </h1>

                    </div>


                    <p
                        class="
                            mt-1
                            text-sm
                            text-slate-500
                        "
                    >
                        Registra y consulta los pagos de las atenciones.
                    </p>

                </div>


                <button
                    type="button"
                    class="
                        flex
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
                        shadow-sm
                        transition
                        hover:bg-clinica-800
                    "
                    @click="
                        drawerAbierto = true
                    "
                >

                    <Plus
                        :size="17"
                    />

                    Registrar pago

                </button>

            </div>


            <!-- FILTROS -->

            <div
                class="
                    mt-6
                    flex
                    flex-col
                    gap-3
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-4
                    shadow-sm
                    sm:flex-row
                "
            >

                <div
                    class="
                        relative
                        flex-1
                    "
                >

                    <Search
                        :size="17"
                        class="
                            absolute
                            left-3
                            top-1/2
                            -translate-y-1/2
                            text-slate-400
                        "
                    />


                    <input
                        v-model="buscar"
                        type="text"
                        class="
                            input-clinica
                            w-full
                            pl-10
                        "
                        placeholder="Buscar paciente, DNI o código..."
                    >

                </div>


                <select
                    v-model="estado"
                    class="
                        input-clinica
                        min-w-44
                    "
                >

                    <option value="">
                        Todos
                    </option>

                    <option value="REGISTRADO">
                        Registrados
                    </option>

                    <option value="ANULADO">
                        Anulados
                    </option>

                </select>

            </div>


            <!-- LISTADO -->

            <div
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

                <div
                    v-if="
                        !pagos.data.length
                    "
                    class="
                        py-20
                        text-center
                    "
                >

                    <ReceiptText
                        :size="38"
                        class="
                            mx-auto
                            text-slate-200
                        "
                    />


                    <p
                        class="
                            mt-3
                            font-semibold
                            text-slate-600
                        "
                    >
                        No hay pagos registrados
                    </p>


                    <p
                        class="
                            mt-1
                            text-sm
                            text-slate-400
                        "
                    >
                        Los pagos aparecerán aquí.
                    </p>

                </div>


                <div
                    v-else
                    class="
                        divide-y
                        divide-slate-100
                    "
                >

                    <div
                        v-for="
                            pago
                            in pagos.data
                        "
                        :key="pago.id"
                        class="
                            flex
                            flex-col
                            gap-4
                            p-4
                            transition
                            hover:bg-slate-50/70
                            md:flex-row
                            md:items-center
                        "
                    >

                        <!-- ICONO -->

                        <div
                            class="
                                flex
                                h-11
                                w-11
                                shrink-0
                                items-center
                                justify-center
                                rounded-xl
                            "
                            :class="
                                pago.estado === 'ANULADO'
                                    ? 'bg-rose-50 text-rose-500'
                                    : 'bg-emerald-50 text-emerald-600'
                            "
                        >

                            <Banknote
                                :size="20"
                            />

                        </div>


                        <!-- INFORMACIÓN -->

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
                                    gap-2
                                "
                            >

                                <p
                                    class="
                                        truncate
                                        font-bold
                                        text-slate-800
                                    "
                                >
                                    {{
                                        pago.cita
                                            ?.paciente
                                            ?.nombres
                                    }}

                                    {{
                                        pago.cita
                                            ?.paciente
                                            ?.apellidos
                                    }}
                                </p>


                                <span
                                    class="
                                        rounded-full
                                        px-2
                                        py-0.5
                                        text-[10px]
                                        font-bold
                                    "
                                    :class="
                                        pago.estado === 'ANULADO'
                                            ? 'bg-rose-100 text-rose-700'
                                            : 'bg-emerald-100 text-emerald-700'
                                    "
                                >
                                    {{ pago.estado }}
                                </span>

                            </div>


                            <p
                                class="
                                    mt-1
                                    truncate
                                    text-sm
                                    text-slate-500
                                "
                            >
                                {{ servicio(pago) }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-xs
                                    text-slate-400
                                "
                            >
                                {{
                                    pago.metodo_pago
                                        ?.nombre
                                }}

                                ·

                                {{
                                    fechaPago(
                                        pago.fecha_pago
                                    )
                                }}
                            </p>

                        </div>


                        <!-- MONTO -->

                        <div
                            class="
                                md:text-right
                            "
                        >

                            <p
                                class="
                                    text-xs
                                    text-slate-400
                                "
                            >
                                Monto
                            </p>


                            <p
                                class="
                                    mt-0.5
                                    text-lg
                                    font-bold
                                "
                                :class="
                                    pago.estado === 'ANULADO'
                                        ? 'text-slate-400 line-through'
                                        : 'text-slate-900'
                                "
                            >
                                {{
                                    dinero(
                                        pago.monto
                                    )
                                }}
                            </p>

                        </div>


                        <!-- ACCIÓN -->

                        <button
                            v-if="
                                pago.estado
                                ===
                                'REGISTRADO'
                            "
                            type="button"
                            class="
                                flex
                                items-center
                                gap-1
                                rounded-lg
                                px-3
                                py-2
                                text-xs
                                font-semibold
                                text-rose-600
                                transition
                                hover:bg-rose-50
                            "
                            @click="
                                abrirAnular(
                                    pago
                                )
                            "
                        >

                            <Ban
                                :size="14"
                            />

                            Anular

                        </button>

                    </div>

                </div>

            </div>


            <!-- PAGINACIÓN -->

            <div
                v-if="
                    pagos.links?.length > 3
                "
                class="
                    mt-5
                    flex
                    flex-wrap
                    justify-center
                    gap-1
                "
            >

                <button
                    v-for="
                        (
                            link,
                            indice
                        )
                        in pagos.links
                    "
                    :key="indice"
                    type="button"
                    class="
                        min-w-9
                        rounded-lg
                        px-3
                        py-2
                        text-xs
                        font-semibold
                    "
                    :class="
                        link.active
                            ? 'bg-clinica-700 text-white'
                            : 'border border-slate-200 bg-white text-slate-500'
                    "
                    :disabled="
                        !link.url
                    "
                    v-html="
                        link.label
                    "
                    @click="
                        link.url
                        &&
                        router.visit(
                            link.url,
                            {
                                preserveScroll: true,
                                preserveState: true,
                            }
                        )
                    "
                />

            </div>

        </div>


        <PagoFormDrawer
            :open="
                drawerAbierto
            "
            :metodos-pago="
                metodosPago
            "
            @close="
                drawerAbierto = false
            "
        />


        <AnularPagoModal
            :open="
                modalAnular
            "
            :pago="
                pagoSeleccionado
            "
            @close="
                modalAnular = false
            "
        />

    </AppLayout>

</template>