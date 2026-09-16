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
    Banknote,
    X,
    Search,
    UserRound,
    LoaderCircle,
    WalletCards,
    CheckCircle2,
    CreditCard,
    ReceiptText,
    ArrowLeft,
    CircleDollarSign,
} from 'lucide-vue-next';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },

    metodosPago: {
        type: Array,
        default: () => [],
    },

    prefill: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits([
    'close',
]);

const form = useForm({
    tipo_origen: '',
    cita_id: '',
    tratamiento_paciente_id: '',
    metodo_pago_id: '',
    monto: '',
    fecha_pago: '',
    numero_operacion: '',
    observaciones: '',
});

const pacienteSeleccionado = ref(null);
const textoPaciente = ref('');
const resultadosPacientes = ref([]);
const buscandoPaciente = ref(false);

const opcionesPago = ref([]);
const cargandoOpciones = ref(false);
const errorOpciones = ref('');

const opcionSeleccionada = ref(null);

let timerPaciente = null;
let peticionPaciente = null;
let peticionOpciones = null;

const saldoSeleccionado = computed(
    () =>
        Number(
            opcionSeleccionada.value?.saldo
            ?? 0
        )
);

const porcentajePago = computed(() => {
    const total =
        Number(
            opcionSeleccionada.value?.total
            ?? 0
        );

    const pagado =
        Number(
            opcionSeleccionada.value?.pagado
            ?? 0
        );

    if (total <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(
            (pagado / total) * 100
        )
    );
});

const pasoActual = computed(() => {
    if (!pacienteSeleccionado.value) {
        return 1;
    }

    if (!opcionSeleccionada.value) {
        return 2;
    }

    return 3;
});

watch(
    [
        () => props.open,
        () => props.prefill,
    ],
    ([abierto]) => {
        if (!abierto) {
            return;
        }

        reiniciar();

        if (props.prefill?.id) {
            aplicarPrefill(
                props.prefill
            );
        }
    },
    {
        deep: true,
    }
);

watch(
    textoPaciente,
    valor => {
        clearTimeout(
            timerPaciente
        );

        if (
            pacienteSeleccionado.value
        ) {
            return;
        }

        const texto =
            valor.trim();

        if (
            texto.length < 2
        ) {
            resultadosPacientes.value = [];
            return;
        }

        timerPaciente =
            setTimeout(
                () =>
                    buscarPacientes(
                        texto
                    ),
                300
            );
    }
);

function hoyLocal() {
    const fecha = new Date();

    const year =
        fecha.getFullYear();

    const month =
        String(
            fecha.getMonth() + 1
        ).padStart(
            2,
            '0'
        );

    const day =
        String(
            fecha.getDate()
        ).padStart(
            2,
            '0'
        );

    return `${year}-${month}-${day}`;
}

function reiniciar() {
    form.reset();
    form.clearErrors();

    form.fecha_pago =
        hoyLocal();

    pacienteSeleccionado.value =
        null;

    textoPaciente.value =
        '';

    resultadosPacientes.value =
        [];

    opcionesPago.value =
        [];

    opcionSeleccionada.value =
        null;

    errorOpciones.value =
        '';
}

function aplicarPrefill(deuda) {
    pacienteSeleccionado.value =
        deuda.paciente
        ?? null;

    opcionesPago.value = [
        deuda,
    ];

    seleccionarOpcion(
        deuda
    );
}

async function buscarPacientes(texto) {
    if (
        typeof window
        ===
        'undefined'
    ) {
        return;
    }

    try {
        peticionPaciente?.abort();

        peticionPaciente =
            new AbortController();

        buscandoPaciente.value =
            true;

        const respuesta =
            await fetch(
                `/clinica/pacientes/buscar?q=${encodeURIComponent(texto)}`,
                {
                    headers: {
                        Accept:
                            'application/json',
                    },

                    signal:
                        peticionPaciente.signal,
                }
            );

        if (!respuesta.ok) {
            throw new Error(
                `HTTP ${respuesta.status}`
            );
        }

        const datos =
            await respuesta.json();

        resultadosPacientes.value =
            Array.isArray(
                datos
            )
                ? datos
                : (
                    datos.data
                    ??
                    datos.pacientes
                    ??
                    []
                );
    }
    catch (error) {
        if (
            error.name
            !==
            'AbortError'
        ) {
            console.error(
                'Error buscando pacientes:',
                error
            );
        }
    }
    finally {
        buscandoPaciente.value =
            false;
    }
}

async function seleccionarPaciente(
    paciente
) {
    pacienteSeleccionado.value =
        paciente;

    textoPaciente.value =
        '';

    resultadosPacientes.value =
        [];

    opcionSeleccionada.value =
        null;

    form.tipo_origen =
        '';

    form.cita_id =
        '';

    form.tratamiento_paciente_id =
        '';

    form.monto =
        '';

    await cargarOpciones(
        paciente.id
    );
}

function quitarPaciente() {
    peticionOpciones?.abort();

    pacienteSeleccionado.value =
        null;

    opcionesPago.value =
        [];

    opcionSeleccionada.value =
        null;

    form.tipo_origen =
        '';

    form.cita_id =
        '';

    form.tratamiento_paciente_id =
        '';

    form.monto =
        '';
}

async function cargarOpciones(
    pacienteId
) {
    if (!pacienteId) {
        return;
    }

    try {
        peticionOpciones?.abort();

        peticionOpciones =
            new AbortController();

        cargandoOpciones.value =
            true;

        errorOpciones.value =
            '';

        const parametros =
            new URLSearchParams({
                paciente_id:
                    pacienteId,
            });

        const respuesta =
            await fetch(
                `/clinica/pagos/buscar-citas?${parametros.toString()}`,
                {
                    headers: {
                        Accept:
                            'application/json',
                    },

                    signal:
                        peticionOpciones.signal,
                }
            );

        if (!respuesta.ok) {
            throw new Error(
                `HTTP ${respuesta.status}`
            );
        }

        const datos =
            await respuesta.json();

        opcionesPago.value =
            Array.isArray(
                datos.opciones
            )
                ? datos.opciones
                : [];
    }
    catch (error) {
        if (
            error.name
            ===
            'AbortError'
        ) {
            return;
        }

        console.error(
            'Error cargando deudas:',
            error
        );

        errorOpciones.value =
            'No se pudieron consultar las cuentas del paciente.';
    }
    finally {
        cargandoOpciones.value =
            false;
    }
}

function seleccionarOpcion(
    opcion
) {
    opcionSeleccionada.value =
        opcion;

    form.tipo_origen =
        opcion.tipo;

    form.cita_id =
        opcion.tipo
        ===
        'CITA_SIMPLE'
            ? opcion.cita_id
            : '';

    form.tratamiento_paciente_id =
        opcion.tipo
        ===
        'TRATAMIENTO'
            ? opcion
                .tratamiento_paciente_id
            : '';

    form.monto =
        Number(
            opcion.saldo
            ??
            0
        ).toFixed(
            2
        );

    form.clearErrors(
        'tipo_origen',
        'cita_id',
        'tratamiento_paciente_id',
        'monto'
    );
}

function dinero(valor) {
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

function cerrar() {
    if (
        form.processing
    ) {
        return;
    }

    peticionPaciente?.abort();
    peticionOpciones?.abort();

    clearTimeout(
        timerPaciente
    );

    emit('close');
}

function guardar() {
    form.clearErrors();

    if (
        !opcionSeleccionada.value
    ) {
        form.setError(
            'tipo_origen',
            'Selecciona qué cuenta se pagará.'
        );

        return;
    }

    const monto =
        Number(
            form.monto
        );

    if (
        !Number.isFinite(
            monto
        )
        ||
        monto <= 0
    ) {
        form.setError(
            'monto',
            'Ingresa un monto válido.'
        );

        return;
    }

    if (
        monto
        >
        saldoSeleccionado.value
    ) {
        form.setError(
            'monto',
            `El monto no puede superar ${dinero(saldoSeleccionado.value)}.`
        );

        return;
    }

    form.post(
        '/clinica/pagos',
        {
            preserveScroll:
                true,

            onSuccess: () => {
                emit('close');
            },

            onError: errores => {
                console.error(
                    'Error registrando pago:',
                    errores
                );
            },
        }
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
                class="fixed inset-0 z-[90] bg-slate-950/45 backdrop-blur-[2px]"
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
                class="fixed inset-y-0 right-0 z-[100] flex w-full max-w-2xl flex-col border-l border-slate-200 bg-slate-50 shadow-2xl"
            >
                <header
                    class="shrink-0 border-b border-slate-200 bg-white px-6 py-5"
                >
                    <div
                        class="flex items-start justify-between gap-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"
                            >
                                <CircleDollarSign :size="22" />
                            </div>

                            <div>
                                <h2 class="text-lg font-black text-slate-950">
                                    Registrar pago
                                </h2>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Selecciona la cuenta y registra el abono
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                            @click="cerrar"
                        >
                            <X :size="19" />
                        </button>
                    </div>

                    <div
                        class="mt-5 grid grid-cols-3 gap-2"
                    >
                        <div
                            v-for="paso in [
                                { numero: 1, texto: 'Paciente' },
                                { numero: 2, texto: 'Cuenta' },
                                { numero: 3, texto: 'Pago' },
                            ]"
                            :key="paso.numero"
                            class="rounded-xl border px-3 py-2.5"
                            :class="
                                pasoActual >= paso.numero
                                    ? 'border-emerald-200 bg-emerald-50'
                                    : 'border-slate-200 bg-slate-50'
                            "
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-black"
                                    :class="
                                        pasoActual >= paso.numero
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-slate-200 text-slate-500'
                                    "
                                >
                                    {{ paso.numero }}
                                </span>

                                <span
                                    class="text-[11px] font-bold"
                                    :class="
                                        pasoActual >= paso.numero
                                            ? 'text-emerald-800'
                                            : 'text-slate-400'
                                    "
                                >
                                    {{ paso.texto }}
                                </span>
                            </div>
                        </div>
                    </div>
                </header>

                <form
                    class="flex min-h-0 flex-1 flex-col"
                    @submit.prevent="guardar"
                >
                    <div
                        class="flex-1 space-y-6 overflow-y-auto p-6"
                    >
                        <section
                            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div
                                class="mb-4 flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p class="text-sm font-black text-slate-900">
                                        1. Paciente
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Busca a la persona que realizará el pago
                                    </p>
                                </div>

                                <button
                                    v-if="
                                        pacienteSeleccionado
                                        &&
                                        !props.prefill?.id
                                    "
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:bg-slate-100"
                                    @click="quitarPaciente"
                                >
                                    <ArrowLeft :size="14" />
                                    Cambiar
                                </button>
                            </div>

                            <div
                                v-if="pacienteSeleccionado"
                                class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4"
                            >
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-clinica-700 shadow-sm"
                                >
                                    <UserRound :size="19" />
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-900">
                                        {{ pacienteSeleccionado.nombres }}
                                        {{ pacienteSeleccionado.apellidos }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{
                                            pacienteSeleccionado.codigo
                                            ?? 'Sin código'
                                        }}
                                        <span
                                            v-if="
                                                pacienteSeleccionado.numero_documento
                                            "
                                        >
                                            ·
                                            {{ pacienteSeleccionado.numero_documento }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div
                                v-else
                                class="relative"
                            >
                                <Search
                                    :size="18"
                                    class="absolute left-3.5 top-3.5 text-slate-400"
                                />

                                <input
                                    v-model="textoPaciente"
                                    type="search"
                                    autocomplete="off"
                                    class="input-clinica pl-10 pr-10"
                                    placeholder="Nombre, DNI o código..."
                                >

                                <LoaderCircle
                                    v-if="buscandoPaciente"
                                    :size="17"
                                    class="absolute right-3.5 top-3.5 animate-spin text-clinica-600"
                                />

                                <div
                                    v-if="
                                        textoPaciente.trim().length >= 2
                                    "
                                    class="absolute left-0 right-0 top-12 z-20 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                >
                                    <button
                                        v-for="paciente in resultadosPacientes"
                                        :key="paciente.id"
                                        type="button"
                                        class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-0 hover:bg-slate-50"
                                        @click="
                                            seleccionarPaciente(
                                                paciente
                                            )
                                        "
                                    >
                                        <UserRound
                                            :size="17"
                                            class="text-slate-400"
                                        />

                                        <div>
                                            <p class="text-sm font-bold text-slate-800">
                                                {{ paciente.nombres }}
                                                {{ paciente.apellidos }}
                                            </p>

                                            <p class="mt-1 text-xs text-slate-400">
                                                {{
                                                    paciente.codigo
                                                    ?? 'Sin código'
                                                }}
                                            </p>
                                        </div>
                                    </button>

                                    <p
                                        v-if="
                                            !buscandoPaciente
                                            &&
                                            resultadosPacientes.length === 0
                                        "
                                        class="px-4 py-5 text-center text-sm text-slate-400"
                                    >
                                        No encontramos pacientes.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section
                            v-if="pacienteSeleccionado"
                            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="mb-4">
                                <p class="text-sm font-black text-slate-900">
                                    2. Cuenta pendiente
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    Elige la cita o tratamiento que se está pagando
                                </p>
                            </div>

                            <div
                                v-if="cargandoOpciones"
                                class="flex items-center justify-center py-10"
                            >
                                <LoaderCircle
                                    :size="24"
                                    class="animate-spin text-clinica-600"
                                />
                            </div>

                            <div
                                v-else-if="opcionesPago.length"
                                class="space-y-3"
                            >
                                <button
                                    v-for="opcion in opcionesPago"
                                    :key="opcion.id"
                                    type="button"
                                    class="w-full rounded-2xl border p-4 text-left transition"
                                    :class="
                                        opcionSeleccionada?.id === opcion.id
                                            ? 'border-emerald-400 bg-emerald-50 ring-1 ring-emerald-200'
                                            : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                    "
                                    @click="
                                        seleccionarOpcion(
                                            opcion
                                        )
                                    "
                                >
                                    <div
                                        class="flex items-start justify-between gap-4"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <p class="text-sm font-black text-slate-900">
                                                    {{ opcion.concepto }}
                                                </p>

                                                <span
                                                    class="rounded-full px-2 py-0.5 text-[10px] font-black"
                                                    :class="
                                                        opcion.tipo === 'TRATAMIENTO'
                                                            ? 'bg-violet-100 text-violet-700'
                                                            : 'bg-sky-100 text-sky-700'
                                                    "
                                                >
                                                    {{
                                                        opcion.tipo === 'TRATAMIENTO'
                                                            ? 'Tratamiento'
                                                            : 'Cita simple'
                                                    }}
                                                </span>
                                            </div>

                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ opcion.detalle }}
                                            </p>
                                        </div>

                                        <CheckCircle2
                                            v-if="
                                                opcionSeleccionada?.id === opcion.id
                                            "
                                            :size="19"
                                            class="shrink-0 text-emerald-600"
                                        />
                                    </div>

                                    <div
                                        class="mt-4 grid grid-cols-3 gap-2 text-center"
                                    >
                                        <div class="rounded-xl bg-white p-2.5">
                                            <p class="text-[10px] uppercase text-slate-400">
                                                Total
                                            </p>

                                            <p class="mt-1 text-xs font-black text-slate-800">
                                                {{ dinero(opcion.total) }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-white p-2.5">
                                            <p class="text-[10px] uppercase text-slate-400">
                                                Pagado
                                            </p>

                                            <p class="mt-1 text-xs font-black text-emerald-700">
                                                {{ dinero(opcion.pagado) }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-white p-2.5">
                                            <p class="text-[10px] uppercase text-slate-400">
                                                Saldo
                                            </p>

                                            <p class="mt-1 text-xs font-black text-rose-700">
                                                {{ dinero(opcion.saldo) }}
                                            </p>
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <div
                                v-else-if="
                                    !cargandoOpciones
                                    &&
                                    !errorOpciones
                                "
                                class="rounded-2xl border border-dashed border-emerald-200 bg-emerald-50 p-5 text-center"
                            >
                                <CheckCircle2
                                    :size="24"
                                    class="mx-auto text-emerald-600"
                                />

                                <p class="mt-2 text-sm font-black text-emerald-800">
                                    Sin cuentas pendientes
                                </p>
                            </div>

                            <p
                                v-if="errorOpciones"
                                class="rounded-xl bg-rose-50 p-3 text-xs text-rose-600"
                            >
                                {{ errorOpciones }}
                            </p>

                            <p
                                v-if="form.errors.tipo_origen"
                                class="error-text"
                            >
                                {{ form.errors.tipo_origen }}
                            </p>
                        </section>

                        <section
                            v-if="opcionSeleccionada"
                            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div
                                class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div>
                                    <p class="text-sm font-black text-slate-900">
                                        3. Datos del pago
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Puedes pagar todo el saldo o registrar un abono
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl bg-rose-50 px-4 py-3 text-right"
                                >
                                    <p class="text-[10px] font-bold uppercase text-rose-500">
                                        Falta pagar
                                    </p>

                                    <p class="mt-1 text-lg font-black text-rose-700">
                                        {{ dinero(saldoSeleccionado) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mb-5">
                                <div
                                    class="mb-2 flex items-center justify-between text-[11px] font-semibold text-slate-500"
                                >
                                    <span>
                                        Ya pagado
                                    </span>

                                    <span>
                                        {{ porcentajePago }}%
                                    </span>
                                </div>

                                <div
                                    class="h-2 overflow-hidden rounded-full bg-slate-100"
                                >
                                    <div
                                        class="h-full rounded-full bg-emerald-500"
                                        :style="{
                                            width: `${porcentajePago}%`,
                                        }"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="form-label">
                                        Monto a registrar
                                    </label>

                                    <div class="relative">
                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-black text-slate-400"
                                        >
                                            S/
                                        </span>

                                        <input
                                            v-model="form.monto"
                                            type="number"
                                            min="0.01"
                                            :max="saldoSeleccionado"
                                            step="0.01"
                                            class="input-clinica pl-10"
                                        >
                                    </div>

                                    <p
                                        v-if="form.errors.monto"
                                        class="error-text"
                                    >
                                        {{ form.errors.monto }}
                                    </p>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Fecha
                                    </label>

                                    <input
                                        v-model="form.fecha_pago"
                                        type="date"
                                        class="input-clinica"
                                    >

                                    <p
                                        v-if="form.errors.fecha_pago"
                                        class="error-text"
                                    >
                                        {{ form.errors.fecha_pago }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5">
                                <label
                                    class="form-label flex items-center gap-2"
                                >
                                    <CreditCard :size="14" />
                                    Método de pago
                                </label>

                                <select
                                    v-model="form.metodo_pago_id"
                                    class="input-clinica"
                                >
                                    <option value="">
                                        Selecciona
                                    </option>

                                    <option
                                        v-for="metodo in metodosPago"
                                        :key="metodo.id"
                                        :value="metodo.id"
                                    >
                                        {{ metodo.nombre }}
                                    </option>
                                </select>

                                <p
                                    v-if="form.errors.metodo_pago_id"
                                    class="error-text"
                                >
                                    {{ form.errors.metodo_pago_id }}
                                </p>
                            </div>

                            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label
                                        class="form-label flex items-center gap-2"
                                    >
                                        <ReceiptText :size="14" />
                                        N° operación
                                    </label>

                                    <input
                                        v-model="form.numero_operacion"
                                        type="text"
                                        maxlength="100"
                                        class="input-clinica"
                                        placeholder="Opcional"
                                    >
                                </div>

                                <div>
                                    <label class="form-label">
                                        Observaciones
                                    </label>

                                    <input
                                        v-model="form.observaciones"
                                        type="text"
                                        class="input-clinica"
                                        placeholder="Opcional"
                                    >
                                </div>
                            </div>
                        </section>
                    </div>

                    <footer
                        class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-200 bg-white p-4"
                    >
                        <p
                            v-if="opcionSeleccionada"
                            class="hidden text-xs text-slate-400 sm:block"
                        >
                            Total de la cuenta:
                            <strong class="text-slate-700">
                                {{ dinero(opcionSeleccionada.total) }}
                            </strong>
                        </p>

                        <div class="ml-auto flex gap-3">
                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                @click="cerrar"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                :disabled="
                                    form.processing
                                    ||
                                    !opcionSeleccionada
                                "
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <LoaderCircle
                                    v-if="form.processing"
                                    :size="17"
                                    class="animate-spin"
                                />

                                <Banknote
                                    v-else
                                    :size="17"
                                />

                                {{
                                    form.processing
                                        ? 'Registrando...'
                                        : 'Confirmar pago'
                                }}
                            </button>
                        </div>
                    </footer>
                </form>
            </aside>
        </Transition>
    </div>
</template>
