<script setup>
import {
    computed,
    ref,
    watch,
    onMounted,
} from 'vue';

import {
    useForm,
} from '@inertiajs/vue3';

import {
    X,
    CheckCircle2,
    CalendarPlus,
    PauseCircle,
    LoaderCircle,
    Clock3,
    Stethoscope,
    DoorOpen,
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

});


const emit = defineEmits([
    'close',
    'success',
]);

/* =========================================================
   CLIENTE / SSR
========================================================= */

const montado =
    ref(false);


onMounted(() => {

    montado.value =
        true;

});

const form = useForm({

    accion_tratamiento:
        'CONTINUAR_SIN_FECHA',

    fecha_hora_siguiente:
        '',

    profesional_id:
        '',

    consultorio_id:
        '',

    motivo_siguiente:
        '',

    observaciones_siguiente:
        '',

});


const tratamiento =
    computed(() => {

        return (
            props.cita
                ?.tratamiento_cita
                ?.tratamiento_paciente
            ??
            props.cita
                ?.tratamientoCita
                ?.tratamientoPaciente
            ??
            null
        );

    });


const servicio =
    computed(() => {

        return (
            tratamiento.value
                ?.servicio
            ??
            props.cita
                ?.servicios_cita
                ?.[0]
                ?.servicio
            ??
            null
        );

    });


const nombrePaciente =
    computed(() => {

        const paciente =
            props.cita?.paciente;

        if (!paciente) {
            return 'Paciente';
        }

        return (
            `${paciente.nombres ?? ''} ${paciente.apellidos ?? ''}`
        ).trim();

    });


const nombreServicio =
    computed(() => {

        return (
            servicio.value?.nombre
            ??
            'Tratamiento'
        );

    });


const programarSiguiente =
    computed(() => {

        return (
            form.accion_tratamiento
            ===
            'PROGRAMAR_SIGUIENTE'
        );

    });


watch(
    () => props.open,

    abierto => {

        if (!abierto) {
            return;
        }


        form.reset();

        form.clearErrors();

        form.accion_tratamiento =
            'CONTINUAR_SIN_FECHA';

        form.profesional_id =
            props.cita?.profesional_id
            ??
            props.cita?.profesional?.id
            ??
            '';

        form.consultorio_id =
            props.cita?.consultorio_id
            ??
            props.cita?.consultorio?.id
            ??
            '';
    }
);


function cerrar() {

    if (form.processing) {
        return;
    }

    emit('close');
}


function guardar() {

    if (!props.cita?.id) {
        return;
    }


    form.clearErrors();


    if (
        programarSiguiente.value
        &&
        !form.fecha_hora_siguiente
    ) {

        form.setError(
            'fecha_hora_siguiente',
            'Selecciona la fecha y hora de la próxima sesión.'
        );

        return;
    }


    if (
        programarSiguiente.value
        &&
        !form.profesional_id
    ) {

        form.setError(
            'profesional_id',
            'Selecciona el profesional.'
        );

        return;
    }


    form.patch(
        `/clinica/citas/${props.cita.id}/finalizar-atencion`,

        {
            preserveScroll:
                true,

            onSuccess: () => {

                emit('success');
            },

            onError: errores => {

                console.error(
                    'Error finalizando atención:',
                    errores
                );
            },
        }
    );
}
</script>


<template>
    <Teleport v-if="montado" to="body">

        <Transition
            enter-active-class="transition duration-200"
            leave-active-class="transition duration-150"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >

            <div
                v-if="open"
                class="
                    fixed
                    inset-0
                    z-[200]
                    flex
                    items-center
                    justify-center
                    bg-slate-950/45
                    p-4
                    backdrop-blur-[2px]
                "
                @click.self="cerrar"
            >

                <div
                    class="
                        w-full
                        max-w-xl
                        overflow-hidden
                        rounded-3xl
                        bg-white
                        shadow-2xl
                    "
                >

                    <header
                        class="
                            flex
                            items-start
                            justify-between
                            border-b
                            border-slate-100
                            px-6
                            py-5
                        "
                    >

                        <div>

                            <div class="flex items-center gap-2">

                                <CheckCircle2
                                    :size="21"
                                    class="text-emerald-600"
                                />

                                <h2 class="text-lg font-bold text-slate-900">
                                    Finalizar atención
                                </h2>

                            </div>

                            <p class="mt-2 text-sm text-slate-500">
                                {{ nombrePaciente }}
                                ·
                                {{ nombreServicio }}
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
                                rounded-xl
                                text-slate-400
                                transition
                                hover:bg-slate-100
                            "
                            @click="cerrar"
                        >
                            <X :size="18" />
                        </button>

                    </header>


                    <form
                        class="
                            max-h-[78vh]
                            overflow-y-auto
                            p-6
                        "
                        @submit.prevent="guardar"
                    >

                        <div
                            v-if="tratamiento"
                            class="
                                mb-5
                                rounded-2xl
                                border
                                border-slate-200
                                bg-slate-50
                                p-4
                            "
                        >
                            <p class="text-xs font-medium text-slate-500">
                                Tratamiento actual
                            </p>

                            <p class="mt-1 text-sm font-bold text-slate-900">
                                {{ nombreServicio }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Estado:
                                {{ tratamiento.estado ?? 'EN_PROCESO' }}
                            </p>
                        </div>


                        <p class="mb-4 text-sm font-semibold text-slate-800">
                            ¿Qué sucederá con este tratamiento?
                        </p>


                        <div class="space-y-3">

                            <button
                                type="button"
                                class="
                                    w-full
                                    rounded-2xl
                                    border
                                    p-4
                                    text-left
                                    transition
                                "
                                :class="
                                    form.accion_tratamiento
                                    === 'PROGRAMAR_SIGUIENTE'
                                        ? 'border-clinica-500 bg-clinica-50 ring-1 ring-clinica-200'
                                        : 'border-slate-200 hover:bg-slate-50'
                                "
                                @click="
                                    form.accion_tratamiento =
                                        'PROGRAMAR_SIGUIENTE'
                                "
                            >
                                <div class="flex gap-3">

                                    <CalendarPlus
                                        :size="20"
                                        class="mt-0.5 shrink-0 text-clinica-700"
                                    />

                                    <div>
                                        <p class="text-sm font-bold text-slate-900">
                                            Programar siguiente sesión
                                        </p>

                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            Finaliza esta cita y crea ahora la próxima
                                            sesión del mismo tratamiento.
                                        </p>
                                    </div>

                                </div>
                            </button>


                            <button
                                type="button"
                                class="
                                    w-full
                                    rounded-2xl
                                    border
                                    p-4
                                    text-left
                                    transition
                                "
                                :class="
                                    form.accion_tratamiento
                                    === 'CONTINUAR_SIN_FECHA'
                                        ? 'border-amber-500 bg-amber-50 ring-1 ring-amber-200'
                                        : 'border-slate-200 hover:bg-slate-50'
                                "
                                @click="
                                    form.accion_tratamiento =
                                        'CONTINUAR_SIN_FECHA'
                                "
                            >
                                <div class="flex gap-3">

                                    <PauseCircle
                                        :size="20"
                                        class="mt-0.5 shrink-0 text-amber-600"
                                    />

                                    <div>
                                        <p class="text-sm font-bold text-slate-900">
                                            Continuar sin fecha
                                        </p>

                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            El tratamiento seguirá EN PROCESO y la
                                            próxima sesión se programará después.
                                        </p>
                                    </div>

                                </div>
                            </button>


                            <button
                                type="button"
                                class="
                                    w-full
                                    rounded-2xl
                                    border
                                    p-4
                                    text-left
                                    transition
                                "
                                :class="
                                    form.accion_tratamiento
                                    === 'COMPLETAR'
                                        ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-200'
                                        : 'border-slate-200 hover:bg-slate-50'
                                "
                                @click="
                                    form.accion_tratamiento =
                                        'COMPLETAR'
                                "
                            >
                                <div class="flex gap-3">

                                    <CheckCircle2
                                        :size="20"
                                        class="mt-0.5 shrink-0 text-emerald-600"
                                    />

                                    <div>
                                        <p class="text-sm font-bold text-slate-900">
                                            Completar tratamiento
                                        </p>

                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            El tratamiento no requiere más sesiones
                                            y quedará marcado como COMPLETADO.
                                        </p>
                                    </div>

                                </div>
                            </button>

                        </div>


                        <div
                            v-if="programarSiguiente"
                            class="
                                mt-6
                                space-y-4
                                rounded-2xl
                                border
                                border-clinica-100
                                bg-clinica-50/40
                                p-4
                            "
                        >

                            <div class="flex items-center gap-2">

                                <Clock3
                                    :size="17"
                                    class="text-clinica-700"
                                />

                                <p class="text-sm font-bold text-slate-900">
                                    Próxima sesión
                                </p>

                            </div>


                            <div>
                                <label class="form-label">
                                    Fecha y hora
                                </label>

                                <input
                                    v-model="form.fecha_hora_siguiente"
                                    type="datetime-local"
                                    class="input-clinica"
                                >

                                <p
                                    v-if="form.errors.fecha_hora_siguiente"
                                    class="error-text"
                                >
                                    {{ form.errors.fecha_hora_siguiente }}
                                </p>
                            </div>


                            <div class="grid gap-4 sm:grid-cols-2">

                                <div>
                                    <label class="form-label flex items-center gap-2">
                                        <Stethoscope :size="14" />
                                        Profesional
                                    </label>

                                    <select
                                        v-model="form.profesional_id"
                                        class="input-clinica"
                                    >
                                        <option value="">
                                            Selecciona
                                        </option>

                                        <option
                                            v-for="profesional in profesionales"
                                            :key="profesional.id"
                                            :value="profesional.id"
                                        >
                                            {{ profesional.nombres }}
                                            {{ profesional.apellidos }}
                                        </option>
                                    </select>

                                    <p
                                        v-if="form.errors.profesional_id"
                                        class="error-text"
                                    >
                                        {{ form.errors.profesional_id }}
                                    </p>
                                </div>


                                <div>
                                    <label class="form-label flex items-center gap-2">
                                        <DoorOpen :size="14" />
                                        Consultorio
                                    </label>

                                    <select
                                        v-model="form.consultorio_id"
                                        class="input-clinica"
                                    >
                                        <option value="">
                                            Sin asignar
                                        </option>

                                        <option
                                            v-for="consultorio in consultorios"
                                            :key="consultorio.id"
                                            :value="consultorio.id"
                                        >
                                            {{ consultorio.nombre }}
                                        </option>
                                    </select>
                                </div>

                            </div>


                            <div>
                                <label class="form-label">
                                    Motivo
                                </label>

                                <input
                                    v-model="form.motivo_siguiente"
                                    type="text"
                                    class="input-clinica"
                                    placeholder="Opcional"
                                >
                            </div>


                            <div>
                                <label class="form-label">
                                    Observaciones
                                </label>

                                <textarea
                                    v-model="form.observaciones_siguiente"
                                    rows="3"
                                    class="input-clinica py-3"
                                    placeholder="Opcional"
                                />
                            </div>

                        </div>


                        <p
                            v-if="form.errors.accion_tratamiento"
                            class="
                                mt-4
                                rounded-xl
                                bg-rose-50
                                p-3
                                text-xs
                                text-rose-600
                            "
                        >
                            {{ form.errors.accion_tratamiento }}
                        </p>


                        <div class="mt-6 flex justify-end gap-3">

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
                                    transition
                                    hover:bg-slate-50
                                "
                                @click="cerrar"
                            >
                                Cancelar
                            </button>


                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="
                                    inline-flex
                                    items-center
                                    gap-2
                                    rounded-xl
                                    bg-emerald-600
                                    px-5
                                    py-2.5
                                    text-sm
                                    font-bold
                                    text-white
                                    transition
                                    hover:bg-emerald-700
                                    disabled:cursor-not-allowed
                                    disabled:opacity-60
                                "
                            >
                                <LoaderCircle
                                    v-if="form.processing"
                                    :size="17"
                                    class="animate-spin"
                                />

                                <CheckCircle2
                                    v-else
                                    :size="17"
                                />

                                {{
                                    form.processing
                                        ? 'Finalizando...'
                                        : 'Finalizar atención'
                                }}
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </Transition>

    </Teleport>
</template>
