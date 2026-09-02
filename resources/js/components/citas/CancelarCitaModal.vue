<script setup>
import {
    watch,
} from 'vue';

import {
    useForm,
} from '@inertiajs/vue3';

import {
    X,
    CalendarX,
    LoaderCircle,
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


const form = useForm({
    motivo_cancelacion: '',
});


watch(
    () => props.open,
    (open) => {

        if (open) {
            form.reset();
            form.clearErrors();
        }

    }
);


function cerrar() {

    if (form.processing) {
        return;
    }

    emit('close');
}


function cancelarCita() {

    if (!props.cita?.id) {
        return;
    }


    form.patch(
        `/clinica/citas/${props.cita.id}/cancelar`,
        {
            preserveScroll: true,

            onSuccess: () => {

                form.reset();

                emit('close');
            },
        }
    );
}
</script>


<template>

    <div>

        <!-- FONDO -->

        <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >

            <div
                v-if="open"
                class="
                    fixed
                    inset-0
                    z-[110]
                    bg-slate-950/45
                    backdrop-blur-[2px]
                "
                @click="cerrar"
            />

        </Transition>


        <!-- MODAL -->

        <Transition
            enter-active-class="transition duration-200 ease-out"
            leave-active-class="transition duration-150 ease-in"
            enter-from-class="scale-95 opacity-0"
            leave-to-class="scale-95 opacity-0"
        >

            <div
                v-if="open"
                class="
                    fixed
                    inset-0
                    z-[120]
                    flex
                    items-center
                    justify-center
                    p-4
                "
            >

                <div
                    class="
                        w-full
                        max-w-md
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        shadow-2xl
                    "
                    @click.stop
                >

                    <!-- HEADER -->

                    <header
                        class="
                            flex
                            items-start
                            justify-between
                            gap-4
                            border-b
                            border-slate-100
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
                                    h-11
                                    w-11
                                    items-center
                                    justify-center
                                    rounded-xl
                                    bg-rose-50
                                    text-rose-600
                                "
                            >
                                <CalendarX :size="21" />
                            </div>


                            <div>

                                <h2
                                    class="
                                        text-base
                                        font-bold
                                        text-slate-900
                                    "
                                >
                                    Cancelar cita
                                </h2>

                                <p
                                    class="
                                        mt-1
                                        text-xs
                                        text-slate-500
                                    "
                                >
                                    La cita dejará de reservar el horario.
                                </p>

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
                                hover:bg-slate-100
                            "
                            @click="cerrar"
                        >
                            <X :size="18" />
                        </button>

                    </header>


                    <!-- PACIENTE -->

                    <div
                        v-if="cita?.paciente"
                        class="
                            mx-5
                            mt-5
                            rounded-xl
                            bg-slate-50
                            p-4
                        "
                    >

                        <p
                            class="
                                text-xs
                                font-medium
                                text-slate-400
                            "
                        >
                            Paciente
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm
                                font-bold
                                text-slate-800
                            "
                        >
                            {{ cita.paciente.nombres }}
                            {{ cita.paciente.apellidos }}
                        </p>

                    </div>


                    <!-- MOTIVO -->

                    <form
                        class="p-5"
                        @submit.prevent="cancelarCita"
                    >

                        <label class="form-label">
                            Motivo de cancelación
                            <span
                                class="
                                    font-normal
                                    text-slate-400
                                "
                            >
                                (opcional)
                            </span>
                        </label>


                        <textarea
                            v-model="form.motivo_cancelacion"
                            rows="4"
                            maxlength="500"
                            class="
                                input-clinica
                                min-h-28
                                py-3
                            "
                            placeholder="Ej. El paciente llamó indicando que no podrá asistir..."
                        />


                        <p
                            v-if="
                                form.errors
                                    .motivo_cancelacion
                            "
                            class="error-text"
                        >
                            {{
                                form.errors
                                    .motivo_cancelacion
                            }}
                        </p>


                        <div
                            class="
                                mt-5
                                flex
                                justify-end
                                gap-3
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
                                    hover:bg-slate-50
                                "
                                @click="cerrar"
                            >
                                Volver
                            </button>


                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="
                                    inline-flex
                                    items-center
                                    gap-2
                                    rounded-xl
                                    bg-rose-600
                                    px-4
                                    py-2.5
                                    text-sm
                                    font-semibold
                                    text-white
                                    transition
                                    hover:bg-rose-700
                                    disabled:opacity-60
                                "
                            >

                                <LoaderCircle
                                    v-if="form.processing"
                                    :size="17"
                                    class="animate-spin"
                                />

                                <CalendarX
                                    v-else
                                    :size="17"
                                />

                                {{
                                    form.processing
                                        ? 'Cancelando...'
                                        : 'Cancelar cita'
                                }}

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </Transition>

    </div>

</template>