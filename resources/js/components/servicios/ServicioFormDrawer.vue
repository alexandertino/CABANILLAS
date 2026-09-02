<script setup>
import {
    computed,
    watch,
} from 'vue';

import { useForm } from '@inertiajs/vue3';

import {
    HeartPulse,
    X,
    Save,
    LoaderCircle,
    Clock3,
    Banknote,
} from 'lucide-vue-next';


const props = defineProps({

    open: {
        type: Boolean,
        default: false,
    },

    servicio: {
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

    duracion_estimada_minutos: 30,

    precio_actual: '',

    activo: true,

});


const editando = computed(() =>
    Boolean(props.servicio?.id)
);


function cargar() {

    if (!props.servicio) {

        form.reset();

        form.duracion_estimada_minutos = 30;
        form.activo = true;

        return;
    }


    form.codigo =
        props.servicio.codigo ?? '';

    form.nombre =
        props.servicio.nombre ?? '';

    form.descripcion =
        props.servicio.descripcion ?? '';

    form.duracion_estimada_minutos =
        props.servicio.duracion_estimada_minutos ?? 30;

    form.precio_actual =
        props.servicio.precio_actual ?? '';

    form.activo =
        props.servicio.activo ?? true;
}


watch(
    [
        () => props.open,
        () => props.servicio,
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
            `/clinica/servicios/${props.servicio.id}`,
            {
                preserveScroll: true,

                onSuccess: () =>
                    emit('close'),
            }
        );

        return;
    }


    form.post(
        '/clinica/servicios',
        {
            preserveScroll: true,

            onSuccess: () => {

                form.reset();

                form.duracion_estimada_minutos = 30;
                form.activo = true;

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


        <!-- DRAWER -->

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
                            <HeartPulse :size="20" />
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
                                        ? 'Editar servicio'
                                        : 'Nuevo servicio'
                                }}
                            </h2>

                            <p
                                class="
                                    text-xs
                                    text-slate-500
                                "
                            >
                                Servicio odontológico
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
                        <X :size="19" />
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
                            space-y-5
                            overflow-y-auto
                            p-6
                        "
                    >

                        <!-- CÓDIGO -->

                        <div>

                            <label class="form-label">
                                Código
                            </label>

                            <input
                                v-model="form.codigo"
                                type="text"
                                placeholder="SERV-001"
                                class="input-clinica"
                            >

                            <p
                                v-if="form.errors.codigo"
                                class="error-text"
                            >
                                {{ form.errors.codigo }}
                            </p>

                        </div>


                        <!-- NOMBRE -->

                        <div>

                            <label class="form-label">
                                Nombre del servicio
                            </label>

                            <input
                                v-model="form.nombre"
                                type="text"
                                placeholder="Limpieza dental"
                                class="input-clinica"
                            >

                            <p
                                v-if="form.errors.nombre"
                                class="error-text"
                            >
                                {{ form.errors.nombre }}
                            </p>

                        </div>


                        <!-- DESCRIPCIÓN -->

                        <div>

                            <label class="form-label">
                                Descripción
                            </label>

                            <textarea
                                v-model="form.descripcion"
                                rows="4"
                                placeholder="Descripción del tratamiento o servicio..."
                                class="
                                    input-clinica
                                    min-h-28
                                    py-3
                                "
                            ></textarea>

                        </div>


                        <!-- DURACIÓN Y PRECIO -->

                        <div
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
                                    <Clock3 :size="14" />

                                    Duración estimada
                                </label>


                                <div class="relative">

                                    <input
                                        v-model="
                                            form.duracion_estimada_minutos
                                        "
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="
                                            input-clinica
                                            pr-16
                                        "
                                    >

                                    <span
                                        class="
                                            absolute
                                            right-3
                                            top-1/2
                                            -translate-y-1/2
                                            text-xs
                                            text-slate-400
                                        "
                                    >
                                        min
                                    </span>

                                </div>


                                <p
                                    v-if="
                                        form.errors
                                            .duracion_estimada_minutos
                                    "
                                    class="error-text"
                                >
                                    {{
                                        form.errors
                                            .duracion_estimada_minutos
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
                                    <Banknote :size="14" />

                                    Precio actual
                                </label>


                                <div class="relative">

                                    <span
                                        class="
                                            absolute
                                            left-3
                                            top-1/2
                                            -translate-y-1/2
                                            text-sm
                                            font-medium
                                            text-slate-400
                                        "
                                    >
                                        S/
                                    </span>

                                    <input
                                        v-model="
                                            form.precio_actual
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="
                                            input-clinica
                                            pl-9
                                        "
                                    >

                                </div>


                                <p
                                    v-if="
                                        form.errors.precio_actual
                                    "
                                    class="error-text"
                                >
                                    {{
                                        form.errors.precio_actual
                                    }}
                                </p>

                            </div>

                        </div>


                        <!-- ACTIVO -->

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
                                    Servicio activo
                                </p>

                                <p
                                    class="
                                        mt-0.5
                                        text-xs
                                        text-slate-500
                                    "
                                >
                                    Podrá seleccionarse al crear citas
                                </p>

                            </div>

                        </label>

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
                                        : 'Guardar servicio'
                            }}

                        </button>

                    </footer>

                </form>

            </aside>

        </Transition>

    </div>

</template>