<script setup>
import {
    computed,
    ref,
    watch,
} from 'vue';

import {
    X,
    Search,
    LoaderCircle,
    WalletCards,
    Stethoscope,
    CalendarDays,
    UserRound,
    Banknote,
    ArrowRight,
    CircleDollarSign,
    UsersRound,
    BadgeDollarSign,
} from 'lucide-vue-next';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    'close',
    'pay',
]);

const buscar = ref('');
const tipo = ref('');
const estadoPago = ref('');

const cargando = ref(false);
const error = ref('');
const deudas = ref([]);

const resumen = ref({
    cuentas: 0,
    pacientes: 0,
    saldo_total: 0,
    pendientes: 0,
    parciales: 0,
});

let peticion = null;
let timerBuscar = null;

const hayFiltros = computed(
    () =>
        Boolean(
            buscar.value
            ||
            tipo.value
            ||
            estadoPago.value
        )
);

watch(
    () => props.open,
    abierto => {
        if (abierto) {
            cargar();
        }
        else {
            peticion?.abort();
        }
    }
);

watch(
    [
        tipo,
        estadoPago,
    ],
    () => {
        if (props.open) {
            cargar();
        }
    }
);

watch(
    buscar,
    () => {
        clearTimeout(timerBuscar);

        if (!props.open) {
            return;
        }

        timerBuscar = setTimeout(
            cargar,
            300
        );
    }
);

async function cargar() {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        peticion?.abort();
        peticion = new AbortController();

        cargando.value = true;
        error.value = '';

        const params = new URLSearchParams();

        if (buscar.value.trim()) {
            params.set(
                'buscar',
                buscar.value.trim()
            );
        }

        if (tipo.value) {
            params.set(
                'tipo',
                tipo.value
            );
        }

        if (estadoPago.value) {
            params.set(
                'estado_pago',
                estadoPago.value
            );
        }

        const respuesta = await fetch(
            `/clinica/pagos/pendientes?${params.toString()}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                signal: peticion.signal,
            }
        );

        if (!respuesta.ok) {
            throw new Error(
                `HTTP ${respuesta.status}`
            );
        }

        const datos = await respuesta.json();

        deudas.value = Array.isArray(datos.data)
            ? datos.data
            : [];

        resumen.value = {
            cuentas:
                Number(datos.resumen?.cuentas ?? 0),

            pacientes:
                Number(datos.resumen?.pacientes ?? 0),

            saldo_total:
                Number(datos.resumen?.saldo_total ?? 0),

            pendientes:
                Number(datos.resumen?.pendientes ?? 0),

            parciales:
                Number(datos.resumen?.parciales ?? 0),
        };
    }
    catch (e) {
        if (e.name === 'AbortError') {
            return;
        }

        console.error(
            'Error cargando pendientes:',
            e
        );

        deudas.value = [];

        error.value =
            'No se pudieron cargar las cuentas pendientes.';
    }
    finally {
        cargando.value = false;
    }
}

function limpiarFiltros() {
    buscar.value = '';
    tipo.value = '';
    estadoPago.value = '';
}

function cerrar() {
    peticion?.abort();
    clearTimeout(timerBuscar);
    emit('close');
}

function pagar(deuda) {
    emit(
        'pay',
        deuda
    );
}

function dinero(valor) {
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

function fecha(valor) {
    if (!valor) {
        return 'Sin fecha';
    }

    return new Intl.DateTimeFormat(
        'es-PE',
        {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'America/Lima',
        }
    ).format(
        new Date(valor)
    );
}

function iniciales(paciente) {
    const a =
        paciente?.nombres
            ?.trim()
            ?.[0]
        ?? '';

    const b =
        paciente?.apellidos
            ?.trim()
            ?.[0]
        ?? '';

    return (
        `${a}${b}`.toUpperCase()
        ||
        'P'
    );
}
</script>

<template>
    <div>
        <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[110] bg-slate-950/45 backdrop-blur-[2px]"
                @click="cerrar"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            leave-active-class="transition-transform duration-200 ease-in"
            enter-from-class="translate-x-full"
            leave-to-class="translate-x-full"
        >
            <aside
                v-if="open"
                class="fixed inset-y-0 right-0 z-[120] flex w-full max-w-4xl flex-col border-l border-slate-200 bg-slate-50 shadow-2xl"
            >
                <header
                    class="shrink-0 border-b border-slate-200 bg-white px-6 py-5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-700"
                                >
                                    <CircleDollarSign :size="22" />
                                </div>

                                <div>
                                    <h2 class="text-lg font-bold text-slate-950">
                                        Cuentas pendientes
                                    </h2>

                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Citas y tratamientos que todavía tienen saldo por cobrar
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            @click="cerrar"
                        >
                            <X :size="19" />
                        </button>
                    </div>

                    <div
                        class="mt-5 grid gap-3 sm:grid-cols-3"
                    >
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="flex items-center gap-2 text-slate-500">
                                <BadgeDollarSign :size="16" />
                                <span class="text-[11px] font-bold uppercase tracking-wide">
                                    Saldo total
                                </span>
                            </div>

                            <p class="mt-2 text-xl font-black text-slate-950">
                                {{ dinero(resumen.saldo_total) }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="flex items-center gap-2 text-slate-500">
                                <WalletCards :size="16" />
                                <span class="text-[11px] font-bold uppercase tracking-wide">
                                    Cuentas
                                </span>
                            </div>

                            <p class="mt-2 text-xl font-black text-slate-950">
                                {{ resumen.cuentas }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="flex items-center gap-2 text-slate-500">
                                <UsersRound :size="16" />
                                <span class="text-[11px] font-bold uppercase tracking-wide">
                                    Pacientes
                                </span>
                            </div>

                            <p class="mt-2 text-xl font-black text-slate-950">
                                {{ resumen.pacientes }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-4 grid gap-3 lg:grid-cols-[1fr_auto_auto]"
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
                                placeholder="Buscar paciente, DNI o código..."
                            >
                        </div>

                        <select
                            v-model="tipo"
                            class="input-clinica"
                        >
                            <option value="">
                                Citas y tratamientos
                            </option>

                            <option value="TRATAMIENTO">
                                Solo tratamientos
                            </option>

                            <option value="CITA_SIMPLE">
                                Solo citas simples
                            </option>
                        </select>

                        <select
                            v-model="estadoPago"
                            class="input-clinica"
                        >
                            <option value="">
                                Todo saldo
                            </option>

                            <option value="PENDIENTE">
                                Sin abonos
                            </option>

                            <option value="PARCIAL">
                                Pago parcial
                            </option>
                        </select>
                    </div>
                </header>

                <div
                    class="min-h-0 flex-1 overflow-y-auto p-6"
                >
                    <div
                        v-if="cargando"
                        class="flex min-h-64 items-center justify-center"
                    >
                        <div class="text-center">
                            <LoaderCircle
                                :size="30"
                                class="mx-auto animate-spin text-clinica-600"
                            />

                            <p class="mt-3 text-sm font-medium text-slate-500">
                                Buscando cuentas pendientes...
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="error"
                        class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-700"
                    >
                        {{ error }}
                    </div>

                    <div
                        v-else-if="deudas.length"
                        class="space-y-4"
                    >
                        <article
                            v-for="deuda in deudas"
                            :key="deuda.id"
                            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div class="p-5">
                                <div
                                    class="flex flex-col gap-5 lg:flex-row lg:items-start"
                                >
                                    <div
                                        class="flex min-w-0 flex-1 gap-4"
                                    >
                                        <div
                                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-sm font-black text-slate-700"
                                        >
                                            {{ iniciales(deuda.paciente) }}
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <p
                                                    class="truncate text-sm font-black text-slate-950"
                                                >
                                                    {{ deuda.paciente?.nombres }}
                                                    {{ deuda.paciente?.apellidos }}
                                                </p>

                                                <span
                                                    class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                                    :class="
                                                        deuda.tipo === 'TRATAMIENTO'
                                                            ? 'bg-violet-100 text-violet-700'
                                                            : 'bg-sky-100 text-sky-700'
                                                    "
                                                >
                                                    {{
                                                        deuda.tipo === 'TRATAMIENTO'
                                                            ? 'TRATAMIENTO'
                                                            : 'CITA SIMPLE'
                                                    }}
                                                </span>

                                                <span
                                                    class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                                    :class="
                                                        deuda.estado_pago === 'PARCIAL'
                                                            ? 'bg-amber-100 text-amber-700'
                                                            : 'bg-rose-100 text-rose-700'
                                                    "
                                                >
                                                    {{
                                                        deuda.estado_pago === 'PARCIAL'
                                                            ? 'PAGO PARCIAL'
                                                            : 'PENDIENTE'
                                                    }}
                                                </span>
                                            </div>

                                            <p
                                                class="mt-2 text-base font-bold text-slate-800"
                                            >
                                                {{ deuda.concepto }}
                                            </p>

                                            <div
                                                class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500"
                                            >
                                                <span
                                                    class="inline-flex items-center gap-1.5"
                                                >
                                                    <UserRound :size="13" />
                                                    {{
                                                        deuda.paciente?.codigo
                                                        ?? 'Sin código'
                                                    }}
                                                </span>

                                                <span
                                                    class="inline-flex items-center gap-1.5"
                                                >
                                                    <CalendarDays :size="13" />
                                                    {{ fecha(deuda.fecha_referencia) }}
                                                </span>

                                                <span
                                                    v-if="deuda.tipo === 'TRATAMIENTO'"
                                                    class="inline-flex items-center gap-1.5"
                                                >
                                                    <Stethoscope :size="13" />
                                                    {{ deuda.detalle }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-slate-800"
                                        @click="pagar(deuda)"
                                    >
                                        Registrar abono
                                        <ArrowRight :size="15" />
                                    </button>
                                </div>

                                <div
                                    class="mt-5 grid gap-3 sm:grid-cols-3"
                                >
                                    <div
                                        class="rounded-2xl bg-slate-50 p-3.5"
                                    >
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                            Total
                                        </p>

                                        <p class="mt-1 text-sm font-black text-slate-800">
                                            {{ dinero(deuda.total) }}
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-2xl bg-emerald-50 p-3.5"
                                    >
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-600">
                                            Pagado
                                        </p>

                                        <p class="mt-1 text-sm font-black text-emerald-800">
                                            {{ dinero(deuda.pagado) }}
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-2xl bg-rose-50 p-3.5"
                                    >
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-rose-600">
                                            Falta pagar
                                        </p>

                                        <p class="mt-1 text-sm font-black text-rose-800">
                                            {{ dinero(deuda.saldo) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div
                                        class="mb-2 flex items-center justify-between text-[11px] font-semibold text-slate-500"
                                    >
                                        <span>
                                            Progreso de pago
                                        </span>

                                        <span>
                                            {{ deuda.progreso }}%
                                        </span>
                                    </div>

                                    <div
                                        class="h-2 overflow-hidden rounded-full bg-slate-100"
                                    >
                                        <div
                                            class="h-full rounded-full bg-emerald-500 transition-all"
                                            :style="{
                                                width: `${deuda.progreso}%`,
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="flex min-h-72 items-center justify-center"
                    >
                        <div class="max-w-sm text-center">
                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-emerald-50 text-emerald-600"
                            >
                                <Banknote :size="28" />
                            </div>

                            <h3 class="mt-4 text-base font-black text-slate-900">
                                No hay cuentas pendientes
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                {{
                                    hayFiltros
                                        ? 'No encontramos resultados con los filtros seleccionados.'
                                        : 'Todos los tratamientos y citas visibles están pagados.'
                                }}
                            </p>

                            <button
                                v-if="hayFiltros"
                                type="button"
                                class="mt-4 rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-white"
                                @click="limpiarFiltros"
                            >
                                Limpiar filtros
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </Transition>
    </div>
</template>
