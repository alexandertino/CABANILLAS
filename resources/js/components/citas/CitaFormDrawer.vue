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
    CalendarDays,
    X,
    Save,
    LoaderCircle,
    Clock3,
    UserRound,
    UserRoundCheck,
    Stethoscope,
    DoorOpen,
    Search,
    HeartPulse,
    Banknote,
    Timer,
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

});


const emit = defineEmits([
    'close',
]);


/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

const form = useForm({

    paciente_id: '',

    profesional_id: '',

    consultorio_id: '',

    estado_cita_id: '',

    servicio_id: '',

    fecha_hora_inicio: '',

    fecha_hora_fin: '',

    motivo: '',

    observaciones: '',
});


const editando = computed(() =>
    Boolean(props.cita?.id)
);

const estadosEditables = computed(() =>
    props.estados.filter(
        estado =>
            estado.codigo !== 'CANCELADA'
    )
);

/*
|--------------------------------------------------------------------------
| PACIENTE
|--------------------------------------------------------------------------
*/

const pacienteSeleccionado = ref(null);

const textoPaciente = ref('');

const resultadosPacientes = ref([]);

const buscandoPaciente = ref(false);

let timerPaciente = null;

let peticionPaciente = null;


watch(
    textoPaciente,
    (valor) => {

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
                () => {
                    buscarPacientes(texto);
                },
                300
            );
    }
);


async function buscarPacientes(
    texto
) {

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

            console.error(
                'Error buscando pacientes:',
                respuesta.status
            );

            resultadosPacientes.value = [];

            return;
        }


        resultadosPacientes.value =
            await respuesta.json();

    }
    catch (error) {

        if (
            error.name !==
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


function seleccionarPaciente(
    paciente
) {

    pacienteSeleccionado.value =
        paciente;

    form.paciente_id =
        paciente.id;

    textoPaciente.value = '';

    resultadosPacientes.value = [];
}


function quitarPaciente() {

    pacienteSeleccionado.value =
        null;

    form.paciente_id = '';

    textoPaciente.value = '';

    resultadosPacientes.value = [];
}


/*
|--------------------------------------------------------------------------
| SERVICIO
|--------------------------------------------------------------------------
*/

const servicioSeleccionado =
    ref(null);

const textoServicio =
    ref('');

const resultadosServicios =
    ref([]);

const buscandoServicio =
    ref(false);

let timerServicio = null;

let peticionServicio = null;


watch(
    textoServicio,
    (valor) => {

        clearTimeout(
            timerServicio
        );


        if (
            servicioSeleccionado.value
        ) {
            return;
        }


        const texto =
            valor.trim();


        if (
            texto.length < 2
        ) {

            resultadosServicios.value = [];

            return;
        }


        timerServicio =
            setTimeout(
                () => {
                    buscarServicios(texto);
                },
                300
            );
    }
);


async function buscarServicios(
    texto
) {

    try {

        peticionServicio?.abort();


        peticionServicio =
            new AbortController();


        buscandoServicio.value =
            true;


        const respuesta =
            await fetch(
                `/clinica/servicios/buscar?q=${encodeURIComponent(texto)}`,
                {
                    headers: {
                        Accept:
                            'application/json',
                    },

                    signal:
                        peticionServicio.signal,
                }
            );


        if (!respuesta.ok) {

            console.error(
                'Error buscando servicios:',
                respuesta.status
            );

            resultadosServicios.value = [];

            return;
        }


        resultadosServicios.value =
            await respuesta.json();

    }
    catch (error) {

        if (
            error.name !==
            'AbortError'
        ) {
            console.error(
                'Error buscando servicios:',
                error
            );
        }

    }
    finally {

        buscandoServicio.value =
            false;
    }
}


function seleccionarServicio(
    servicio
) {

    servicioSeleccionado.value =
        servicio;

    form.servicio_id =
        servicio.id;

    textoServicio.value = '';

    resultadosServicios.value = [];

    calcularFin();
}


function quitarServicio() {

    servicioSeleccionado.value =
        null;

    form.servicio_id = '';

    textoServicio.value = '';

    resultadosServicios.value = [];

    form.fecha_hora_fin = '';
}


/*
|--------------------------------------------------------------------------
| DURACIÓN
|--------------------------------------------------------------------------
*/

const duracionServicio =
    computed(() => {

        return Number(
            servicioSeleccionado
                .value
                ?.duracion_estimada_minutos
            ?? 0
        );
    });


watch(
    [
        () =>
            form.fecha_hora_inicio,

        duracionServicio,
    ],
    calcularFin
);


function calcularFin() {

    if (
        !form.fecha_hora_inicio ||
        duracionServicio.value <= 0
    ) {

        form.fecha_hora_fin = '';

        return;
    }


    const inicio =
        new Date(
            `${form.fecha_hora_inicio}:00`
        );


    if (
        Number.isNaN(
            inicio.getTime()
        )
    ) {

        form.fecha_hora_fin = '';

        return;
    }


    inicio.setMinutes(
        inicio.getMinutes() +
        duracionServicio.value
    );


    form.fecha_hora_fin =
        fechaParaInput(
            inicio
        );
}


function fechaParaInput(
    fecha
) {

    const year =
        fecha.getFullYear();

    const month =
        String(
            fecha.getMonth() + 1
        ).padStart(2, '0');

    const day =
        String(
            fecha.getDate()
        ).padStart(2, '0');

    const hour =
        String(
            fecha.getHours()
        ).padStart(2, '0');

    const minute =
        String(
            fecha.getMinutes()
        ).padStart(2, '0');


    return (
        `${year}-${month}-${day}` +
        `T${hour}:${minute}`
    );
}


function normalizarFechaHora(
    valor
) {

    if (!valor) {
        return '';
    }


    return String(valor)
        .replace(' ', 'T')
        .slice(0, 16);
}


/*
|--------------------------------------------------------------------------
| FORMATO
|--------------------------------------------------------------------------
*/

function formatoPrecio(
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
        Number(valor ?? 0)
    );
}


function formatoDuracion(
    minutos
) {

    const total =
        Number(minutos ?? 0);


    if (
        total < 60
    ) {
        return `${total} min`;
    }


    const horas =
        Math.floor(
            total / 60
        );

    const resto =
        total % 60;


    if (
        resto === 0
    ) {
        return `${horas} h`;
    }


    return (
        `${horas} h ` +
        `${resto} min`
    );
}


function horaFinal() {

    if (
        !form.fecha_hora_fin
    ) {
        return '--:--';
    }


    return (
        form.fecha_hora_fin
            .split('T')[1]
        ?? '--:--'
    );
}


/*
|--------------------------------------------------------------------------
| CARGAR EDICIÓN
|--------------------------------------------------------------------------
*/

function cargar() {

    form.clearErrors();

    textoPaciente.value = '';

    textoServicio.value = '';

    resultadosPacientes.value = [];

    resultadosServicios.value = [];


    if (
        !props.cita
    ) {

        form.reset();

        form.estado_cita_id =
            props.estadoPendiente
            ?? '';

        pacienteSeleccionado.value =
            null;

        servicioSeleccionado.value =
            null;

        return;
    }


    form.paciente_id =
        props.cita.paciente_id
        ?? '';


    pacienteSeleccionado.value =
        props.cita.paciente
        ?? null;


    form.profesional_id =
        props.cita.profesional_id
        ?? '';


    form.consultorio_id =
        props.cita.consultorio_id
        ?? '';


    form.estado_cita_id =
        props.cita.estado_cita_id
        ??
        props.estadoPendiente
        ??
        '';


    /*
     * Una cita tendrá un servicio.
     */

    const relacionServicio =
        props.cita.servicios_cita?.[0]
        ?? null;


    servicioSeleccionado.value =
        relacionServicio?.servicio
        ?? null;


    form.servicio_id =
        relacionServicio?.servicio_id
        ??
        relacionServicio?.servicio?.id
        ??
        '';


    form.fecha_hora_inicio =
        normalizarFechaHora(
            props.cita.fecha_hora_inicio
        );


    form.fecha_hora_fin =
        normalizarFechaHora(
            props.cita.fecha_hora_fin
        );


    form.motivo =
        props.cita.motivo
        ?? '';


    form.observaciones =
        props.cita.observaciones
        ?? '';


    calcularFin();
}


watch(
    [
        () => props.open,
        () => props.cita,
    ],

    ([open]) => {

        if (open) {
            cargar();
        }
    },

    {
        immediate: true,
    }
);


/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/

function cerrar() {

    if (
        form.processing
    ) {
        return;
    }


    emit('close');
}


function guardar() {

    form.clearErrors();

    let hayError = false;


    if (!form.paciente_id) {

        form.setError(
            'paciente_id',
            'Selecciona un paciente.'
        );

        hayError = true;
    }


    if (!form.servicio_id) {

        form.setError(
            'servicio_id',
            'Selecciona un servicio.'
        );

        hayError = true;
    }


    if (!form.profesional_id) {

        form.setError(
            'profesional_id',
            'Selecciona un profesional.'
        );

        hayError = true;
    }


    if (!form.fecha_hora_inicio) {

        form.setError(
            'fecha_hora_inicio',
            'Selecciona la fecha y hora.'
        );

        hayError = true;
    }


    if (hayError) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | EDITAR
    |--------------------------------------------------------------------------
    */

    if (editando.value) {

        form.put(
            `/clinica/citas/${props.cita.id}`,
            {
                preserveScroll: true,

                onSuccess: () => {

                    console.log(
                        'Cita actualizada correctamente'
                    );

                    emit('close');
                },

                onError: (errores) => {

    console.error(
        'ERRORES AL CREAR CITA'
    );

    console.log(
        JSON.stringify(
            errores,
            null,
            2
        )
    );

    console.table(
        errores
    );
},
            }
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    form.post(
        '/clinica/citas',
        {
            preserveScroll: true,

            onSuccess: () => {

                console.log(
                    'Cita creada correctamente'
                );

                form.reset();

                pacienteSeleccionado.value =
                    null;

                servicioSeleccionado.value =
                    null;

                textoPaciente.value = '';

                textoServicio.value = '';

                form.estado_cita_id =
                    props.estadoPendiente ?? '';

                emit('close');
            },

            onError: (errores) => {

                console.error(
                    'Errores al crear cita:',
                    errores
                );
            },

            onFinish: () => {

                console.log(
                    'Petición de cita terminada'
                );
            },
        }
    );
}

</script>


<template>

    <div>

        <!-- FONDO -->

        <Transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >

            <div
                v-if="open"
                class="
                    fixed
                    inset-0
                    z-[90]
                    bg-slate-950/40
                    backdrop-blur-[2px]
                "
                @click="cerrar"
            />

        </Transition>


        <!-- DRAWER -->

        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            leave-active-class="transition-transform duration-200 ease-in"
            enter-from-class="translate-x-full"
            leave-to-class="translate-x-full"
        >

            <aside
                v-if="open"
                class="
                    fixed
                    inset-y-0
                    right-0
                    z-[100]
                    flex
                    w-full
                    max-w-2xl
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
                        items-center
                        justify-between
                        border-b
                        border-slate-200
                        px-6
                        py-5
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
                                    text-lg
                                    font-bold
                                    text-slate-900
                                "
                            >
                                {{
                                    editando
                                        ? 'Editar cita'
                                        : 'Nueva cita'
                                }}
                            </h2>

                            <p
                                class="
                                    mt-0.5
                                    text-xs
                                    text-slate-500
                                "
                            >
                                Programa una atención odontológica
                            </p>

                        </div>

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
                            text-slate-400
                            hover:bg-slate-100
                        "
                        @click="cerrar"
                    >

                        <X :size="20" />

                    </button>

                </header>


                <form
                    class="
                        flex
                        min-h-0
                        flex-1
                        flex-col
                    "
                    @submit.prevent="guardar"
                >

                    <div
                        class="
                            flex-1
                            space-y-7
                            overflow-y-auto
                            p-6
                        "
                    >

                        <!-- PACIENTE -->

                        <section>

                            <label
                                class="
                                    form-label
                                    flex
                                    items-center
                                    gap-2
                                "
                            >

                                <UserRound
                                    :size="15"
                                />

                                Paciente

                            </label>


                            <!-- ELEGIDO -->

                            <div
                                v-if="
                                    pacienteSeleccionado
                                "
                                class="
                                    flex
                                    items-center
                                    gap-3
                                    rounded-2xl
                                    border
                                    border-clinica-200
                                    bg-clinica-50/50
                                    p-4
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
                                        bg-white
                                        text-clinica-700
                                    "
                                >

                                    <UserRoundCheck
                                        :size="20"
                                    />

                                </div>


                                <div
                                    class="
                                        min-w-0
                                        flex-1
                                    "
                                >

                                    <p
                                        class="
                                            truncate
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        {{
                                            pacienteSeleccionado.nombres
                                        }}

                                        {{
                                            pacienteSeleccionado.apellidos
                                        }}
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                        {{
                                            pacienteSeleccionado.codigo
                                        }}

                                        ·

                                        {{
                                            pacienteSeleccionado.numero_documento
                                        }}
                                    </p>

                                </div>


                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="
                                        inline-flex
                                        items-center
                                        gap-2
                                        rounded-xl
                                        bg-clinica-700
                                        px-5
                                        py-2.5
                                        text-sm
                                        font-semibold
                                        text-white
                                        disabled:cursor-not-allowed
                                        disabled:opacity-60
                                    "
                                >
                                    <LoaderCircle
                                        v-if="form.processing"
                                        :size="17"
                                        class="animate-spin"
                                    />

                                    <Save
                                        v-else
                                        :size="17"
                                    />

                                    {{
                                        form.processing
                                            ? 'Guardando...'
                                            : editando
                                                ? 'Guardar cambios'
                                                : 'Programar cita'
                                    }}
                                </button>

                            </div>


                            <!-- BUSCADOR -->

                            <div
                                v-else
                                class="relative"
                            >

                                <Search
                                    :size="18"
                                    class="
                                        absolute
                                        left-3.5
                                        top-3.5
                                        text-slate-400
                                    "
                                />


                                <input
                                    v-model="
                                        textoPaciente
                                    "
                                    type="search"
                                    autocomplete="off"
                                    class="
                                        input-clinica
                                        pl-10
                                        pr-10
                                    "
                                    placeholder="Nombre, DNI o código..."
                                >


                                <LoaderCircle
                                    v-if="
                                        buscandoPaciente
                                    "
                                    :size="17"
                                    class="
                                        absolute
                                        right-3.5
                                        top-3.5
                                        animate-spin
                                        text-clinica-600
                                    "
                                />


                                <div
                                    v-if="
                                        textoPaciente
                                            .trim()
                                            .length >= 2
                                    "
                                    class="
                                        mt-2
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        shadow-lg
                                    "
                                >

                                    <button
                                        v-for="
                                            paciente
                                            in resultadosPacientes
                                        "
                                        :key="
                                            paciente.id
                                        "
                                        type="button"
                                        class="
                                            flex
                                            w-full
                                            items-center
                                            gap-3
                                            border-b
                                            border-slate-100
                                            px-4
                                            py-3
                                            text-left
                                            last:border-0
                                            hover:bg-slate-50
                                        "
                                        @click="
                                            seleccionarPaciente(
                                                paciente
                                            )
                                        "
                                    >

                                        <UserRound
                                            :size="18"
                                            class="
                                                text-slate-400
                                            "
                                        />


                                        <div>

                                            <p
                                                class="
                                                    text-sm
                                                    font-semibold
                                                    text-slate-800
                                                "
                                            >
                                                {{
                                                    paciente.nombres
                                                }}

                                                {{
                                                    paciente.apellidos
                                                }}
                                            </p>


                                            <p
                                                class="
                                                    mt-1
                                                    text-xs
                                                    text-slate-400
                                                "
                                            >
                                                {{
                                                    paciente.codigo
                                                }}

                                                ·

                                                {{
                                                    paciente.numero_documento
                                                }}
                                            </p>

                                        </div>

                                    </button>


                                    <p
                                        v-if="
                                            !buscandoPaciente &&
                                            resultadosPacientes.length === 0
                                        "
                                        class="
                                            px-4
                                            py-5
                                            text-center
                                            text-sm
                                            text-slate-400
                                        "
                                    >
                                        No encontramos pacientes.
                                    </p>

                                </div>

                            </div>


                            <p
                                v-if="
                                    form.errors
                                        .paciente_id
                                "
                                class="error-text"
                            >
                                {{
                                    form.errors
                                        .paciente_id
                                }}
                            </p>

                        </section>


                        <!-- SERVICIO -->

                        <section>

                            <label
                                class="
                                    form-label
                                    flex
                                    items-center
                                    gap-2
                                "
                            >

                                <HeartPulse
                                    :size="15"
                                />

                                Servicio

                            </label>


                            <!-- SERVICIO ELEGIDO -->

                            <div
                                v-if="
                                    servicioSeleccionado
                                "
                                class="
                                    rounded-2xl
                                    border
                                    border-clinica-200
                                    bg-clinica-50/50
                                    p-4
                                "
                            >

                                <div
                                    class="
                                        flex
                                        items-start
                                        justify-between
                                        gap-4
                                    "
                                >

                                    <div>

                                        <p
                                            class="
                                                text-sm
                                                font-bold
                                                text-slate-900
                                            "
                                        >
                                            {{
                                                servicioSeleccionado.nombre
                                            }}
                                        </p>


                                        <div
                                            class="
                                                mt-2
                                                flex
                                                flex-wrap
                                                gap-4
                                                text-xs
                                            "
                                        >

                                            <span
                                                class="
                                                    inline-flex
                                                    items-center
                                                    gap-1
                                                    text-slate-500
                                                "
                                            >

                                                <Clock3
                                                    :size="13"
                                                />

                                                {{
                                                    formatoDuracion(
                                                        duracionServicio
                                                    )
                                                }}

                                            </span>


                                            <span
                                                class="
                                                    inline-flex
                                                    items-center
                                                    gap-1
                                                    font-semibold
                                                    text-clinica-700
                                                "
                                            >

                                                <Banknote
                                                    :size="13"
                                                />

                                                {{
                                                    formatoPrecio(
                                                        servicioSeleccionado
                                                            .precio_actual
                                                    )
                                                }}

                                            </span>

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
                                            rounded-lg
                                            text-slate-400
                                            hover:bg-white
                                            hover:text-rose-600
                                        "
                                        @click="
                                            quitarServicio
                                        "
                                    >

                                        <X :size="17" />

                                    </button>

                                </div>

                            </div>


                            <!-- BUSCADOR SERVICIO -->

                            <div
                                v-else
                                class="relative"
                            >

                                <Search
                                    :size="18"
                                    class="
                                        absolute
                                        left-3.5
                                        top-3.5
                                        text-slate-400
                                    "
                                />


                                <input
                                    v-model="
                                        textoServicio
                                    "
                                    type="search"
                                    autocomplete="off"
                                    class="
                                        input-clinica
                                        pl-10
                                        pr-10
                                    "
                                    placeholder="Buscar servicio..."
                                >


                                <LoaderCircle
                                    v-if="
                                        buscandoServicio
                                    "
                                    :size="17"
                                    class="
                                        absolute
                                        right-3.5
                                        top-3.5
                                        animate-spin
                                        text-clinica-600
                                    "
                                />


                                <div
                                    v-if="
                                        textoServicio
                                            .trim()
                                            .length >= 2
                                    "
                                    class="
                                        mt-2
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        shadow-lg
                                    "
                                >

                                    <button
                                        v-for="
                                            servicio
                                            in resultadosServicios
                                        "
                                        :key="
                                            servicio.id
                                        "
                                        type="button"
                                        class="
                                            flex
                                            w-full
                                            items-center
                                            justify-between
                                            gap-4
                                            border-b
                                            border-slate-100
                                            px-4
                                            py-3
                                            text-left
                                            last:border-0
                                            hover:bg-slate-50
                                        "
                                        @click="
                                            seleccionarServicio(
                                                servicio
                                            )
                                        "
                                    >

                                        <div>

                                            <p
                                                class="
                                                    text-sm
                                                    font-semibold
                                                    text-slate-800
                                                "
                                            >
                                                {{
                                                    servicio.nombre
                                                }}
                                            </p>


                                            <p
                                                class="
                                                    mt-1
                                                    text-xs
                                                    text-slate-400
                                                "
                                            >
                                                {{
                                                    servicio.codigo
                                                }}
                                            </p>

                                        </div>


                                        <div
                                            class="
                                                shrink-0
                                                text-right
                                            "
                                        >

                                            <p
                                                class="
                                                    text-xs
                                                    text-slate-500
                                                "
                                            >
                                                {{
                                                    servicio
                                                        .duracion_estimada_minutos
                                                }}
                                                min
                                            </p>

                                            <p
                                                class="
                                                    mt-1
                                                    text-xs
                                                    font-semibold
                                                    text-clinica-700
                                                "
                                            >
                                                {{
                                                    formatoPrecio(
                                                        servicio
                                                            .precio_actual
                                                    )
                                                }}
                                            </p>

                                        </div>

                                    </button>


                                    <p
                                        v-if="
                                            !buscandoServicio &&
                                            resultadosServicios.length === 0
                                        "
                                        class="
                                            px-4
                                            py-5
                                            text-center
                                            text-sm
                                            text-slate-400
                                        "
                                    >
                                        No encontramos servicios.
                                    </p>

                                </div>

                            </div>


                            <p
                                v-if="
                                    form.errors
                                        .servicio_id
                                "
                                class="error-text"
                            >
                                {{
                                    form.errors
                                        .servicio_id
                                }}
                            </p>

                        </section>


                        <!-- PROFESIONAL Y CONSULTORIO -->

                        <section
                            class="
                                grid
                                gap-5
                                sm:grid-cols-2
                            "
                        >

                            <div>

                                <label
                                    class="
                                        form-label
                                        flex
                                        items-center
                                        gap-2
                                    "
                                >

                                    <Stethoscope
                                        :size="14"
                                    />

                                    Profesional

                                </label>


                                <select
                                    v-model="
                                        form.profesional_id
                                    "
                                    class="input-clinica"
                                >

                                    <option value="">
                                        Selecciona
                                    </option>


                                    <option
                                        v-for="
                                            profesional
                                            in profesionales
                                        "
                                        :key="
                                            profesional.id
                                        "
                                        :value="
                                            profesional.id
                                        "
                                    >
                                        {{
                                            profesional.nombres
                                        }}

                                        {{
                                            profesional.apellidos
                                        }}
                                    </option>

                                </select>


                                <p
                                    v-if="
                                        form.errors
                                            .profesional_id
                                    "
                                    class="error-text"
                                >
                                    {{
                                        form.errors
                                            .profesional_id
                                    }}
                                </p>

                            </div>


                            <div>

                                <label
                                    class="
                                        form-label
                                        flex
                                        items-center
                                        gap-2
                                    "
                                >

                                    <DoorOpen
                                        :size="14"
                                    />

                                    Consultorio

                                </label>


                                <select
                                    v-model="
                                        form.consultorio_id
                                    "
                                    class="input-clinica"
                                >

                                    <option value="">
                                        Sin asignar
                                    </option>


                                    <option
                                        v-for="
                                            consultorio
                                            in consultorios
                                        "
                                        :key="
                                            consultorio.id
                                        "
                                        :value="
                                            consultorio.id
                                        "
                                    >
                                        {{
                                            consultorio.nombre
                                        }}
                                    </option>

                                </select>

                            </div>

                        </section>


                        <!-- HORARIO -->

                        <section
                            class="
                                border-t
                                border-slate-100
                                pt-6
                            "
                        >

                            <div
                                class="
                                    mb-4
                                    flex
                                    items-center
                                    gap-2
                                "
                            >

                                <Timer
                                    :size="17"
                                    class="
                                        text-clinica-700
                                    "
                                />

                                <h3
                                    class="
                                        text-sm
                                        font-bold
                                        text-slate-900
                                    "
                                >
                                    Horario
                                </h3>

                            </div>


                            <div
                                class="
                                    grid
                                    gap-5
                                    sm:grid-cols-2
                                "
                            >

                                <div>

                                    <label
                                        class="form-label"
                                    >
                                        Inicio
                                    </label>


                                    <input
                                        v-model="
                                            form.fecha_hora_inicio
                                        "
                                        type="datetime-local"
                                        class="input-clinica"
                                    >


                                    <p
                                        v-if="
                                            form.errors
                                                .fecha_hora_inicio
                                        "
                                        class="error-text"
                                    >
                                        {{
                                            form.errors
                                                .fecha_hora_inicio
                                        }}
                                    </p>

                                </div>


                                <div>

                                    <label
                                        class="form-label"
                                    >
                                        Fin estimado
                                    </label>


                                    <div
                                        class="
                                            flex
                                            h-11
                                            items-center
                                            rounded-xl
                                            border
                                            border-slate-200
                                            bg-slate-50
                                            px-4
                                        "
                                    >

                                        <Clock3
                                            :size="16"
                                            class="
                                                mr-2
                                                text-slate-400
                                            "
                                        />


                                        <span
                                            class="
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                            "
                                        >
                                            {{
                                                horaFinal()
                                            }}
                                        </span>


                                        <span
                                            v-if="
                                                duracionServicio
                                            "
                                            class="
                                                ml-2
                                                text-xs
                                                text-slate-400
                                            "
                                        >
                                            {{
                                                formatoDuracion(
                                                    duracionServicio
                                                )
                                            }}
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </section>

                        <!-- ESTADO DE LA CITA -->

                        <section
                            v-if="editando"
                            class="
                                border-t
                                border-slate-100
                                pt-6
                            "
                        >
                            <label class="form-label">
                                Estado de la cita
                            </label>

                            <select
                                v-model="form.estado_cita_id"
                                class="input-clinica"
                            >
                                <option
                                    v-for="estado in estadosEditables"
                                    :key="estado.id"
                                    :value="estado.id"
                                >
                                    {{ estado.nombre }}
                                </option>
                            </select>

                            <p
                                v-if="form.errors.estado_cita_id"
                                class="error-text"
                            >
                                {{ form.errors.estado_cita_id }}
                            </p>

                            <p
                                class="
                                    mt-2
                                    text-xs
                                    text-slate-400
                                "
                            >
                                Para cancelar una cita utiliza la acción
                                específica de cancelación.
                            </p>
                        </section>

                        <!-- MOTIVO -->

                        <div>

                            <label
                                class="form-label"
                            >
                                Motivo
                            </label>


                            <input
                                v-model="
                                    form.motivo
                                "
                                type="text"
                                class="input-clinica"
                                placeholder="Motivo de la cita..."
                            >

                        </div>


                        <!-- OBSERVACIONES -->

                        <div>

                            <label
                                class="form-label"
                            >
                                Observaciones
                            </label>


                            <textarea
                                v-model="
                                    form.observaciones
                                "
                                rows="4"
                                class="
                                    input-clinica
                                    min-h-28
                                    py-3
                                "
                                placeholder="Información adicional..."
                            />

                        </div>

                    </div>


                    <!-- FOOTER -->

                    <footer
                        class="
                            flex
                            justify-end
                            gap-3
                            border-t
                            border-slate-200
                            p-4
                        "
                    >

                        <button
                            type="button"
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                px-4
                                py-2.5
                                text-sm
                                font-semibold
                                text-slate-700
                            "
                            @click="cerrar"
                        >
                            Cancelar
                        </button>


                        <button
                            type="submit"
                            :disabled="
                                form.processing
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-clinica-700
                                px-5
                                py-2.5
                                text-sm
                                font-semibold
                                text-white
                                disabled:opacity-60
                            "
                        >

                            <LoaderCircle
                                v-if="
                                    form.processing
                                "
                                :size="17"
                                class="
                                    animate-spin
                                "
                            />

                            <Save
                                v-else
                                :size="17"
                            />


                            {{
                                form.processing
                                    ? 'Guardando...'
                                    : editando
                                        ? 'Guardar cambios'
                                        : 'Programar cita'
                            }}

                        </button>

                    </footer>

                </form>

            </aside>

        </Transition>

    </div>

</template>