<script setup>
import {
    computed,
    watch,
} from 'vue';

import { useForm } from '@inertiajs/vue3';

import {
    DoorOpen,
    X,
    Save,
    LoaderCircle,
} from 'lucide-vue-next';


const props = defineProps({

    open: {
        type: Boolean,
        default: false,
    },

    consultorio: {
        type: Object,
        default: null,
    },

});


const emit = defineEmits([
    'close',
]);


const form = useForm({

    codigo: '',
    nombre: '',
    descripcion: '',
    activo: true,

});


const editando = computed(() =>
    Boolean(props.consultorio?.id)
);


function cargar() {

    if (!props.consultorio) {

        form.reset();
        form.activo = true;

        return;
    }

    form.codigo =
        props.consultorio.codigo ?? '';

    form.nombre =
        props.consultorio.nombre ?? '';

    form.descripcion =
        props.consultorio.descripcion ?? '';

    form.activo =
        props.consultorio.activo ?? true;
}


watch(
    [
        () => props.open,
        () => props.consultorio,
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

    if (!form.processing) {
        emit('close');
    }
}


function guardar() {

    if (editando.value) {

        form.put(
            `/clinica/consultorios/${props.consultorio.id}`,
            {
                preserveScroll: true,

                onSuccess: () =>
                    emit('close'),
            }
        );

        return;
    }


    form.post(
        '/clinica/consultorios',
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
                "
                @click="cerrar"
            ></div>
        </Transition>


        <Transition
            enter-active-class="transition-transform duration-300"
            leave-active-class="transition-transform duration-200"
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
                    max-w-lg
                    flex-col
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
                        p-5
                    "
                >

                    <div class="flex items-center gap-3">

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
                            <DoorOpen :size="20" />
                        </div>

                        <div>

                            <h2
                                class="
                                    font-bold
                                    text-slate-900
                                "
                            >
                                {{
                                    editando
                                        ? 'Editar consultorio'
                                        : 'Nuevo consultorio'
                                }}
                            </h2>

                            <p
                                class="
                                    text-xs
                                    text-slate-500
                                "
                            >
                                Ambiente de atención
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        @click="cerrar"
                    >
                        <X :size="20" />
                    </button>

                </header>


                <form
                    class="
                        flex
                        flex-1
                        flex-col
                    "
                    @submit.prevent="guardar"
                >

                    <div
                        class="
                            flex-1
                            space-y-5
                            overflow-y-auto
                            p-6
                        "
                    >

                        <div>

                            <label class="form-label">
                                Código
                            </label>

                            <input
                                v-model="form.codigo"
                                type="text"
                                placeholder="CONS-01"
                                class="input-clinica"
                            >

                            <p
                                v-if="form.errors.codigo"
                                class="error-text"
                            >
                                {{ form.errors.codigo }}
                            </p>

                        </div>


                        <div>

                            <label class="form-label">
                                Nombre
                            </label>

                            <input
                                v-model="form.nombre"
                                type="text"
                                placeholder="Consultorio 1"
                                class="input-clinica"
                            >

                            <p
                                v-if="form.errors.nombre"
                                class="error-text"
                            >
                                {{ form.errors.nombre }}
                            </p>

                        </div>


                        <div>

                            <label class="form-label">
                                Descripción
                            </label>

                            <textarea
                                v-model="form.descripcion"
                                rows="5"
                                placeholder="Descripción del ambiente..."
                                class="
                                    input-clinica
                                    min-h-32
                                    py-3
                                "
                            ></textarea>

                        </div>


                        <label
                            class="
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
                                    Consultorio activo
                                </p>

                                <p
                                    class="
                                        mt-0.5
                                        text-xs
                                        text-slate-500
                                    "
                                >
                                    Podrá asignarse a citas
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
                                editando
                                    ? 'Guardar cambios'
                                    : 'Guardar consultorio'
                            }}

                        </button>

                    </footer>

                </form>

            </aside>

        </Transition>

    </div>

</template>