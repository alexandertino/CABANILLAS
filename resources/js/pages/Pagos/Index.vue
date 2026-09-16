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
    Banknote,
    Plus,
    Search,
    CalendarDays,
    WalletCards,
    Ban,
    CreditCard,
    CircleDollarSign,
    ArrowUpRight,
    ReceiptText,
    SlidersHorizontal,
    Clock3,
} from 'lucide-vue-next';

import AppLayout
    from '@/layouts/AppLayout.vue';

import PagoFormDrawer
    from '@/components/pagos/PagoFormDrawer.vue';

import AnularPagoModal
    from '@/components/pagos/AnularPagoModal.vue';

import DeudasPendientesDrawer
    from '@/components/pagos/DeudasPendientesDrawer.vue';

const props = defineProps({
    pagos: {
        type: Object,
        required: true,
    },

    metodosPago: {
        type: Array,
        default: () => [],
    },

    resumen: {
        type: Object,
        default: () => ({}),
    },

    filtros: {
        type: Object,
        default: () => ({}),
    },
});

const buscar = ref(
    props.filtros.buscar
    ?? ''
);

const estado = ref(
    props.filtros.estado
    ?? ''
);

const metodoPagoId = ref(
    props.filtros.metodo_pago_id
    ?? ''
);

const drawerPagoAbierto =
    ref(false);

const drawerDeudasAbierto =
    ref(false);

const pagoPrefill =
    ref(null);

const modalAnularAbierto =
    ref(false);

const pagoAnular =
    ref(null);

let timerBusqueda =
    null;

watch(
    buscar,
    () => {
        clearTimeout(
            timerBusqueda
        );

        timerBusqueda =
            setTimeout(
                aplicarFiltros,
                350
            );
    }
);

watch(
    [
        estado,
        metodoPagoId,
    ],
    aplicarFiltros
);

function aplicarFiltros() {
    router.get(
        '/clinica/pagos',
        {
            buscar:
                buscar.value
                ||
                undefined,

            estado:
                estado.value
                ||
                undefined,

            metodo_pago_id:
                metodoPagoId.value
                ||
                undefined,
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

function nuevoPago() {
    pagoPrefill.value =
        null;

    drawerPagoAbierto.value =
        true;
}

function cerrarPago() {
    drawerPagoAbierto.value =
        false;

    pagoPrefill.value =
        null;
}

function pagarDeuda(
    deuda
) {
    drawerDeudasAbierto.value =
        false;

    pagoPrefill.value =
        deuda;

    drawerPagoAbierto.value =
        true;
}

function abrirAnular(
    pago
) {
    pagoAnular.value =
        pago;

    modalAnularAbierto.value =
        true;
}

function cerrarAnular() {
    modalAnularAbierto.value =
        false;

    pagoAnular.value =
        null;
}

function limpiarFiltros() {
    buscar.value =
        '';

    estado.value =
        '';

    metodoPagoId.value =
        '';
}

function dinero(
    valor
) {
    return new Intl.NumberFormat(
        'es-PE',
        {
            style:
                'currency',

            currency:
                'PEN',
        }
    ).format(
        Number(
            valor
            ??
            0
        )
    );
}

function fechaHora(
    valor
) {
    if (!valor) {
        return '—';
    }

    return new Intl.DateTimeFormat(
        'es-PE',
        {
            day:
                '2-digit',

            month:
                'short',

            year:
                'numeric',

            hour:
                '2-digit',

            minute:
                '2-digit',

            timeZone:
                'America/Lima',
        }
    ).format(
        new Date(
            valor
        )
    );
}

function nombreUsuario(
    pago
) {
    return (
        pago.usuario_registro?.name
        ??
        '—'
    );
}

function referenciaPago(
    pago
) {
    return (
        pago.numero_operacion
        ??
        null
    );
}
</script>

<template>
    <Head title="Pagos" />

    <AppLayout
        titulo="Pagos"
        descripcion="Cobros, abonos y cuentas por cobrar"
    >
        <!-- HERO -->

        <section
            class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 p-6 text-white shadow-xl sm:p-8"
        >
            <div
                class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between"
            >
                <div class="max-w-2xl">
                    <div
                        class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-bold text-emerald-100 backdrop-blur"
                    >
                        <CircleDollarSign :size="15" />
                        Control financiero
                    </div>

                    <h2
                        class="text-2xl font-black tracking-tight sm:text-3xl"
                    >
                        Pagos de la clínica
                    </h2>

                    <p
                        class="mt-3 max-w-xl text-sm leading-6 text-slate-300"
                    >
                        Registra abonos, revisa movimientos y consulta qué
                        tratamientos o citas todavía tienen saldo pendiente.
                    </p>
                </div>

                <div
                    class="flex flex-col gap-3 sm:flex-row"
                >
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-amber-300/20 bg-amber-400 px-5 py-3 text-sm font-black text-amber-950 shadow-lg shadow-amber-950/20 transition hover:-translate-y-0.5 hover:bg-amber-300"
                        @click="
                            drawerDeudasAbierto =
                                true
                        "
                    >
                        <WalletCards :size="18" />
                        Ver cuentas pendientes
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-slate-100"
                        @click="nuevoPago"
                    >
                        <Plus :size="18" />
                        Registrar pago
                    </button>
                </div>
            </div>
        </section>

        <!-- RESUMEN -->

        <section
            class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        >
            <article
                class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            >
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <p
                            class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400"
                        >
                            Cobrado hoy
                        </p>

                        <p
                            class="mt-3 text-2xl font-black text-slate-950"
                        >
                            {{
                                dinero(
                                    resumen.registrado_hoy
                                )
                            }}
                        </p>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            Solo pagos registrados
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"
                    >
                        <Banknote :size="20" />
                    </div>
                </div>
            </article>

            <article
                class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            >
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <p
                            class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400"
                        >
                            Movimientos hoy
                        </p>

                        <p
                            class="mt-3 text-2xl font-black text-slate-950"
                        >
                            {{
                                resumen.pagos_hoy
                                ??
                                0
                            }}
                        </p>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            Pagos realizados hoy
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-50 text-sky-700"
                    >
                        <CalendarDays :size="20" />
                    </div>
                </div>
            </article>

            <article
                class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            >
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <p
                            class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400"
                        >
                            Registrados
                        </p>

                        <p
                            class="mt-3 text-2xl font-black text-slate-950"
                        >
                            {{
                                resumen.registrados
                                ??
                                0
                            }}
                        </p>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            Movimientos válidos
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-700"
                    >
                        <ReceiptText :size="20" />
                    </div>
                </div>
            </article>

            <article
                class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            >
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <p
                            class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400"
                        >
                            Anulados
                        </p>

                        <p
                            class="mt-3 text-2xl font-black text-slate-950"
                        >
                            {{
                                resumen.anulados
                                ??
                                0
                            }}
                        </p>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            Conservados para auditoría
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-700"
                    >
                        <Ban :size="20" />
                    </div>
                </div>
            </article>
        </section>

        <!-- MOVIMIENTOS -->

        <section
            class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
        >
            <header
                class="border-b border-slate-100 px-5 py-5 sm:px-6"
            >
                <div
                    class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between"
                >
                    <div>
                        <div
                            class="flex items-center gap-2"
                        >
                            <ReceiptText
                                :size="19"
                                class="text-slate-500"
                            />

                            <h3
                                class="text-base font-black text-slate-950"
                            >
                                Movimientos
                            </h3>
                        </div>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            {{
                                pagos.total
                                ??
                                0
                            }}
                            pagos encontrados
                        </p>
                    </div>

                    <div
                        class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_auto_auto_auto]"
                    >
                        <div class="relative">
                            <Search
                                :size="17"
                                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
                            />

                            <input
                                v-model="buscar"
                                type="search"
                                class="input-clinica pl-10"
                                placeholder="Paciente, DNI o código..."
                            >
                        </div>

                        <select
                            v-model="metodoPagoId"
                            class="input-clinica"
                        >
                            <option value="">
                                Todos los métodos
                            </option>

                            <option
                                v-for="
                                    metodo
                                    in metodosPago
                                "
                                :key="metodo.id"
                                :value="metodo.id"
                            >
                                {{ metodo.nombre }}
                            </option>
                        </select>

                        <select
                            v-model="estado"
                            class="input-clinica"
                        >
                            <option value="">
                                Todos los estados
                            </option>

                            <option value="REGISTRADO">
                                Registrados
                            </option>

                            <option value="ANULADO">
                                Anulados
                            </option>
                        </select>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                            @click="limpiarFiltros"
                        >
                            <SlidersHorizontal :size="15" />
                            Limpiar
                        </button>
                    </div>
                </div>
            </header>

            <div
                v-if="
                    pagos.data?.length
                "
                class="divide-y divide-slate-100"
            >
                <article
                    v-for="
                        pago
                        in pagos.data
                    "
                    :key="pago.id"
                    class="group px-5 py-5 transition hover:bg-slate-50/70 sm:px-6"
                >
                    <div
                        class="grid gap-5 xl:grid-cols-[minmax(260px,1.4fr)_minmax(150px,.7fr)_minmax(150px,.7fr)_minmax(210px,.9fr)_auto] xl:items-center"
                    >
                        <div
                            class="flex min-w-0 items-start gap-3"
                        >
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                                :class="
                                    pago.estado
                                    ===
                                    'ANULADO'
                                        ? 'bg-rose-50 text-rose-600'
                                        : 'bg-emerald-50 text-emerald-700'
                                "
                            >
                                <Banknote :size="19" />
                            </div>

                            <div class="min-w-0">
                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <p
                                        class="truncate text-sm font-black text-slate-950"
                                    >
                                        {{
                                            pago.paciente
                                                ?.nombres
                                        }}
                                        {{
                                            pago.paciente
                                                ?.apellidos
                                        }}
                                    </p>

                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-black"
                                        :class="
                                            pago.tipo_origen
                                            ===
                                            'TRATAMIENTO'
                                                ? 'bg-violet-100 text-violet-700'
                                                : 'bg-sky-100 text-sky-700'
                                        "
                                    >
                                        {{
                                            pago.tipo_origen
                                            ===
                                            'TRATAMIENTO'
                                                ? 'TRATAMIENTO'
                                                : 'CITA SIMPLE'
                                        }}
                                    </span>
                                </div>

                                <p
                                    class="mt-1 truncate text-sm font-semibold text-slate-600"
                                >
                                    {{ pago.concepto }}
                                </p>

                                <p
                                    class="mt-1 text-xs text-slate-400"
                                >
                                    {{
                                        pago.paciente
                                            ?.codigo
                                        ??
                                        'Sin código'
                                    }}

                                    <span
                                        v-if="
                                            referenciaPago(
                                                pago
                                            )
                                        "
                                    >
                                        · Operación
                                        {{
                                            referenciaPago(
                                                pago
                                            )
                                        }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-wide text-slate-400"
                            >
                                Monto
                            </p>

                            <p
                                class="mt-1 text-lg font-black"
                                :class="
                                    pago.estado
                                    ===
                                    'ANULADO'
                                        ? 'text-slate-400 line-through'
                                        : 'text-slate-950'
                                "
                            >
                                {{
                                    dinero(
                                        pago.monto
                                    )
                                }}
                            </p>

                            <span
                                class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-black"
                                :class="
                                    pago.estado
                                    ===
                                    'ANULADO'
                                        ? 'bg-rose-100 text-rose-700'
                                        : 'bg-emerald-100 text-emerald-700'
                                "
                            >
                                {{ pago.estado }}
                            </span>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-wide text-slate-400"
                            >
                                Método
                            </p>

                            <p
                                class="mt-2 flex items-center gap-2 text-sm font-bold text-slate-700"
                            >
                                <CreditCard
                                    :size="15"
                                    class="text-slate-400"
                                />

                                {{
                                    pago.metodo_pago
                                        ?.nombre
                                    ??
                                    '—'
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-wide text-slate-400"
                            >
                                Registro
                            </p>

                            <p
                                class="mt-2 flex items-center gap-2 text-sm font-semibold text-slate-700"
                            >
                                <Clock3
                                    :size="14"
                                    class="text-slate-400"
                                />

                                {{
                                    fechaHora(
                                        pago.fecha_pago
                                    )
                                }}
                            </p>

                            <p
                                class="mt-1 text-xs text-slate-400"
                            >
                                Por
                                {{
                                    nombreUsuario(
                                        pago
                                    )
                                }}
                            </p>
                        </div>

                        <div
                            class="flex justify-start xl:justify-end"
                        >
                            <button
                                v-if="
                                    pago.estado
                                    ===
                                    'REGISTRADO'
                                "
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-3.5 py-2.5 text-xs font-bold text-rose-600 transition hover:border-rose-300 hover:bg-rose-50"
                                @click="
                                    abrirAnular(
                                        pago
                                    )
                                "
                            >
                                <Ban :size="14" />
                                Anular
                            </button>

                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600"
                            >
                                <Ban :size="13" />
                                Anulado
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="
                            pago.estado
                            ===
                            'ANULADO'
                        "
                        class="mt-4 rounded-2xl border border-rose-100 bg-rose-50/80 px-4 py-3"
                    >
                        <p
                            class="text-xs font-bold text-rose-700"
                        >
                            Motivo:
                            {{
                                pago.motivo_anulacion
                                ??
                                'Sin motivo registrado'
                            }}
                        </p>

                        <p
                            class="mt-1 text-[11px] text-rose-500"
                        >
                            {{
                                fechaHora(
                                    pago.fecha_anulacion
                                )
                            }}
                            ·
                            {{
                                pago.usuario_anulacion
                                    ?.name
                                ??
                                '—'
                            }}
                        </p>
                    </div>
                </article>
            </div>

            <div
                v-else
                class="px-6 py-16 text-center"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400"
                >
                    <ReceiptText :size="27" />
                </div>

                <h3
                    class="mt-4 text-base font-black text-slate-900"
                >
                    No hay movimientos
                </h3>

                <p
                    class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500"
                >
                    No encontramos pagos con los filtros seleccionados.
                </p>
            </div>

            <footer
                v-if="
                    pagos.links?.length > 3
                "
                class="flex flex-wrap justify-center gap-2 border-t border-slate-100 px-5 py-5"
            >
                <button
                    v-for="
                        link
                        in pagos.links
                    "
                    :key="link.label"
                    type="button"
                    :disabled="!link.url"
                    class="rounded-xl border px-3 py-2 text-xs font-bold transition disabled:cursor-not-allowed disabled:opacity-40"
                    :class="
                        link.active
                            ? [
                                'border-slate-950',
                                'bg-slate-950',
                                'text-white',
                            ]
                            : [
                                'border-slate-200',
                                'bg-white',
                                'text-slate-600',
                                'hover:bg-slate-50',
                            ]
                    "
                    @click="
                        link.url
                        &&
                        router.visit(
                            link.url,
                            {
                                preserveScroll:
                                    true,

                                preserveState:
                                    true,
                            }
                        )
                    "
                    v-html="link.label"
                />
            </footer>
        </section>

        <div
            class="mt-5 flex items-center justify-end"
        >
            <button
                type="button"
                class="group inline-flex items-center gap-2 text-xs font-bold text-slate-500 transition hover:text-slate-900"
                @click="
                    drawerDeudasAbierto =
                        true
                "
            >
                ¿Quién falta pagar?
                <ArrowUpRight
                    :size="15"
                    class="transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5"
                />
            </button>
        </div>

        <PagoFormDrawer
            :open="drawerPagoAbierto"
            :metodos-pago="metodosPago"
            :prefill="pagoPrefill"
            @close="cerrarPago"
        />

        <DeudasPendientesDrawer
            :open="drawerDeudasAbierto"
            @close="
                drawerDeudasAbierto =
                    false
            "
            @pay="pagarDeuda"
        />

        <AnularPagoModal
            :open="modalAnularAbierto"
            :pago="pagoAnular"
            @close="cerrarAnular"
        />
    </AppLayout>
</template>
