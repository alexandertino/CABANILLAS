<script setup>
import {
    computed,
    watch,
} from 'vue';

import { useForm } from '@inertiajs/vue3';

import {
    X,
    Stethoscope,
    Save,
    LoaderCircle,
    BadgeCheck,
} from 'lucide-vue-next';


const props = defineProps({

    open: {
        type: Boolean,
        default: false,
    },

    profesional: {
        type: Object,
        default: null,
    },

});


const emit = defineEmits([
    'close',
]);


const form = useForm({

    nombres: '',
    apellidos: '',

    numero_documento: '',
    numero_colegiatura: '',

    telefono: '',
    correo: '',

    activo: true,

});


const editando = computed(() =>
    Boolean(props.profesional?.id)
);


function cargar() {

    if (!props.profesional) {

        form.reset();
        form.activo = true;

        return;
    }

    form.nombres =
        props.profesional.nombres ?? '';

    form.apellidos =
        props.profesional.apellidos ?? '';

    form.numero_documento =
        props.profesional.numero_documento ?? '';

    form.numero_colegiatura =
        props.profesional.numero_colegiatura ?? '';

    form.telefono =
        props.profesional.telefono ?? '';

    form.correo =
        props.profesional.correo ?? '';

    form.activo =
        props.profesional.activo ?? true;
}


watch(
    [
        () => props.open,
        () => props.profesional,
    ],

    ([open]) => {

        if (open) {
            cargar();
        }

        if (!open) {
            form.clearErrors();
        }

    },

    {
        immediate: true,
    }
);


function cerrar() {

    if (form.processing) {
        return;
    }

    emit('close');
}


function guardar() {

    if (editando.value) {

        form.put(
            `/clinica/profesionales/${props.profesional.id}`,

            {
                preserveScroll: true,

                onSuccess: () => {
                    emit('close');
                },
            }
        );

        return;
    }


    form.post(
        '/clinica/profesionales',

        {
            preserveScroll: true,

            onSuccess: () => {

                form.reset();
                form.activo = true;

                emit('close');
            },
        }
    );
}
</script>


<template>

    <div>

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
            ></div>

        </Transition>


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
                    max-w-xl
                    flex-col
                    border-l
                    border-slate-200
                    bg-white
                    shadow-2xl
                "
            >

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
                            <Stethoscope :size="21" />
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
                                        ? 'Editar profesional'
                                        : 'Nuevo profesional'
                                }}
                            </h2>

                            <p
                                class="
                                    mt-0.5
                                    text-xs
                                    text-slate-500
                                "
                            >
                                Datos del profesional odontológico
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
                            overflow-y-auto
                            p-6
                        "
                    >

                        <div
                            class="
                                grid
                                gap-5
                                sm:grid-cols-2
                            "
                        >

                            <div>

                                <label class="form-label">
                                    Nombres
                                </label>

                                <input
                                    v-model="form.nombres"
                                    type="text"
                                    class="input-clinica"
                                    placeholder="Nombres"
                                >

                                <p
                                    v-if="form.errors.nombres"
                                    class="error-text"
                                >
                                    {{ form.errors.nombres }}
                                </p>

                            </div>


                            <div>

                                <label class="form-label">
                                    Apellidos
                                </label>

                                <input
                                    v-model="form.apellidos"
                                    type="text"
                                    class="input-clinica"
                                    placeholder="Apellidos"
                                >

                                <p
                                    v-if="form.errors.apellidos"
                                    class="error-text"
                                >
                                    {{ form.errors.apellidos }}
                                </p>

                            </div>


                            <div>

                                <label class="form-label">
                                    Documento
                                </label>

                                <input
                                    v-model="form.numero_documento"
                                    type="text"
                                    class="input-clinica"
                                    placeholder="Número de documento"
                                >

                                <p
                                    v-if="
                                        form.errors.numero_documento
                                    "
                                    class="error-text"
                                >
                                    {{
                                        form.errors.numero_documento
                                    }}
                                </p>

                            </div>


                            <div>

                                <label
                                    class="
                                        form-label
                                        flex
                                        items-center
                                        gap-1.5
                                    "
                                >
                                    <BadgeCheck :size="14" />

                                    Colegiatura
                                </label>

                                <input
                                    v-model="form.numero_colegiatura"
                                    type="text"
                                    class="input-clinica"
                                    placeholder="Número de colegiatura"
                                >

                                <p
                                    v-if="
                                        form.errors.numero_colegiatura
                                    "
                                    class="error-text"
                                >
                                    {{
                                        form.errors.numero_colegiatura
                                    }}
                                </p>

                            </div>


                            <div>

                                <label class="form-label">
                                    Teléfono
                                </label>

                                <input
                                    v-model="form.telefono"
                                    type="tel"
                                    class="input-clinica"
                                    placeholder="987654321"
                                >

                            </div>


                            <div>

                                <label class="form-label">
                                    Correo
                                </label>

                                <input
                                    v-model="form.correo"
                                    type="email"
                                    class="input-clinica"
                                    placeholder="odontologo@correo.com"
                                >

                                <p
                                    v-if="form.errors.correo"
                                    class="error-text"
                                >
                                    {{ form.errors.correo }}
                                </p>

                            </div>

                        </div>


                        <label
                            class="
                                mt-7
                                flex
                                cursor-pointer
                                items-center
                                gap-3
                                rounded-xl
                                border
                                border-slate-200
                                bg-slate-50
                                p-4
                            "
                        >

                            <input
                                v-model="form.activo"
                                type="checkbox"
                                class="
                                    h-4
                                    w-4
                                    accent-clinica-700
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
                                    Profesional activo
                                </p>

                                <p
                                    class="
                                        mt-0.5
                                        text-xs
                                        text-slate-500
                                    "
                                >
                                    Podrá ser asignado a nuevas citas
                                </p>

                            </div>

                        </label>

                    </div>


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
                                        : 'Guardar profesional'
                            }}

                        </button>

                    </footer>

                </form>

            </aside>

        </Transition>

    </div>

</template>