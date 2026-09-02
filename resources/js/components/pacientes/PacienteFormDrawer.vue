<script setup>
import { useForm } from '@inertiajs/vue3';

import {
    computed,
    watch,
} from 'vue';

import {
    X,
    UserRound,
    Phone,
    HeartHandshake,
    Save,
    LoaderCircle,
} from 'lucide-vue-next';


const props = defineProps({

    open: {
        type: Boolean,
        default: false,
    },

    paciente: {
        type: Object,
        default: null,
    },

});

const emit = defineEmits([
    'close',
]);


const form = useForm({
    tipo_documento: 'DNI',
    numero_documento: '',

    nombres: '',
    apellidos: '',
    fecha_nacimiento: '',

    telefono: '',
    correo: '',
    direccion: '',

    nombre_contacto_emergencia: '',
    telefono_contacto_emergencia: '',

    observaciones: '',

    activo: true,
});

const editando = computed(() =>
    Boolean(props.paciente?.id)
);

const tituloDrawer = computed(() =>
    editando.value
        ? 'Editar paciente'
        : 'Nuevo paciente'
);

function cargarPaciente() {

    if (!props.paciente) {

        form.reset();

        form.tipo_documento = 'DNI';
        form.activo = true;

        return;
    }


    form.tipo_documento =
        props.paciente.tipo_documento ?? 'DNI';

    form.numero_documento =
        props.paciente.numero_documento ?? '';

    form.nombres =
        props.paciente.nombres ?? '';

    form.apellidos =
        props.paciente.apellidos ?? '';

    form.fecha_nacimiento =
        normalizarFecha(
            props.paciente.fecha_nacimiento
        );

    form.telefono =
        props.paciente.telefono ?? '';

    form.correo =
        props.paciente.correo ?? '';

    form.direccion =
        props.paciente.direccion ?? '';

    form.nombre_contacto_emergencia =
        props.paciente.nombre_contacto_emergencia ?? '';

    form.telefono_contacto_emergencia =
        props.paciente.telefono_contacto_emergencia ?? '';

    form.observaciones =
        props.paciente.observaciones ?? '';

    form.activo =
        props.paciente.activo ?? true;
}

function cerrar() {

    if (form.processing) {
        return;
    }

    emit('close');
}


function guardar() {

    if (editando.value) {

        form.put(
            `/clinica/pacientes/${props.paciente.id}`,
            {
                preserveScroll: true,

                onSuccess: () => {

                    form.reset();

                    emit('close');
                },
            }
        );

        return;
    }


    form.post(
        '/clinica/pacientes',
        {
            preserveScroll: true,

            onSuccess: () => {

                form.reset();

                form.tipo_documento = 'DNI';
                form.activo = true;

                emit('close');
            },
        }
    );
}

watch(
    [
        () => props.open,
        () => props.paciente,
    ],
    ([open]) => {

        if (open) {
            cargarPaciente();
        }

        if (!open) {
            form.clearErrors();
        }

    },
    {
        immediate: true,
    }
);

function normalizarFecha(fecha) {
    if (!fecha) {
        return '';
    }

    return String(fecha).slice(0, 10);
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

                <!-- CABECERA -->

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
                            <UserRound :size="21" />
                        </div>

                        <div>

                            <h2
                                class="
                                    text-lg
                                    font-bold
                                    text-slate-900
                                "
                            >
                                Nuevo paciente
                            </h2>

                            <p
                                class="
                                    mt-0.5
                                    text-xs
                                    text-slate-500
                                "
                            >
                                {{ descripcionDrawer }}
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
                            transition
                            hover:bg-slate-100
                            hover:text-slate-700
                        "
                        @click="cerrar"
                    >
                        <X :size="20" />
                    </button>

                </header>


                <!-- FORMULARIO -->

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
                            px-6
                            py-6
                        "
                    >

                        <!-- DATOS PERSONALES -->

                        <section>

                            <div
                                class="
                                    mb-5
                                    flex
                                    items-center
                                    gap-3
                                "
                            >

                                <div
                                    class="
                                        flex
                                        h-9
                                        w-9
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-slate-100
                                        text-slate-600
                                    "
                                >
                                    <UserRound :size="17" />
                                </div>

                                <div>
                                    <h3
                                        class="
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        Datos personales
                                    </h3>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                        Información principal
                                    </p>
                                </div>

                            </div>


                            <div
                                class="
                                    grid
                                    gap-5
                                    sm:grid-cols-2
                                "
                            >

                                <!-- CÓDIGO -->

                            <!--
                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        Código interno
                                    </label>

                                    <input
                                        v-model="form.codigo"
                                        type="text"
                                        placeholder="PAC-001"
                                        class="input-clinica"
                                        :class="{
                                            'input-error':
                                                form.errors.codigo
                                        }"
                                    >

                                    <p
                                        v-if="form.errors.codigo"
                                        class="error-text"
                                    >
                                        {{ form.errors.codigo }}
                                    </p>

                                </div>
                            -->

                                <!-- DOCUMENTO -->

                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        Tipo de documento
                                    </label>

                                    <select
                                        v-model="form.tipo_documento"
                                        class="input-clinica"
                                    >
                                        <option value="DNI">
                                            DNI
                                        </option>

                                        <option value="CE">
                                            Carné de extranjería
                                        </option>

                                        <option value="PASAPORTE">
                                            Pasaporte
                                        </option>

                                        <option value="OTRO">
                                            Otro
                                        </option>
                                    </select>

                                </div>


                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        Número de documento
                                    </label>

                                    <input
                                        v-model="form.numero_documento"
                                        type="text"
                                        placeholder="00000000"
                                        class="input-clinica"
                                        :class="{
                                            'input-error':
                                                form.errors.numero_documento
                                        }"
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
                                            mb-2
                                            block
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        Fecha de nacimiento
                                    </label>

                                    <input
                                        v-model="form.fecha_nacimiento"
                                        type="date"
                                        class="input-clinica"
                                    >

                                    <p
                                        v-if="
                                            form.errors.fecha_nacimiento
                                        "
                                        class="error-text"
                                    >
                                        {{
                                            form.errors.fecha_nacimiento
                                        }}
                                    </p>

                                </div>


                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        Nombres
                                    </label>

                                    <input
                                        v-model="form.nombres"
                                        type="text"
                                        placeholder="Nombres"
                                        class="input-clinica"
                                        :class="{
                                            'input-error':
                                                form.errors.nombres
                                        }"
                                    >

                                    <p
                                        v-if="form.errors.nombres"
                                        class="error-text"
                                    >
                                        {{ form.errors.nombres }}
                                    </p>

                                </div>


                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        Apellidos
                                    </label>

                                    <input
                                        v-model="form.apellidos"
                                        type="text"
                                        placeholder="Apellidos"
                                        class="input-clinica"
                                        :class="{
                                            'input-error':
                                                form.errors.apellidos
                                        }"
                                    >

                                    <p
                                        v-if="form.errors.apellidos"
                                        class="error-text"
                                    >
                                        {{ form.errors.apellidos }}
                                    </p>

                                </div>

                            </div>

                        </section>


                        <!-- CONTACTO -->

                        <section
                            class="
                                mt-9
                                border-t
                                border-slate-100
                                pt-7
                            "
                        >

                            <div
                                class="
                                    mb-5
                                    flex
                                    items-center
                                    gap-3
                                "
                            >

                                <div
                                    class="
                                        flex
                                        h-9
                                        w-9
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-blue-50
                                        text-blue-600
                                    "
                                >
                                    <Phone :size="17" />
                                </div>

                                <div>

                                    <h3
                                        class="
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        Contacto
                                    </h3>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                        Información de comunicación
                                    </p>

                                </div>

                            </div>


                            <div
                                class="
                                    grid
                                    gap-5
                                    sm:grid-cols-2
                                "
                            >

                                <div>

                                    <label class="form-label">
                                        Teléfono
                                    </label>

                                    <input
                                        v-model="form.telefono"
                                        type="tel"
                                        placeholder="987 654 321"
                                        class="input-clinica"
                                    >

                                    <p
                                        v-if="form.errors.telefono"
                                        class="error-text"
                                    >
                                        {{ form.errors.telefono }}
                                    </p>

                                </div>


                                <div>

                                    <label class="form-label">
                                        Correo electrónico
                                    </label>

                                    <input
                                        v-model="form.correo"
                                        type="email"
                                        placeholder="paciente@correo.com"
                                        class="input-clinica"
                                    >

                                    <p
                                        v-if="form.errors.correo"
                                        class="error-text"
                                    >
                                        {{ form.errors.correo }}
                                    </p>

                                </div>


                                <div class="sm:col-span-2">

                                    <label class="form-label">
                                        Dirección
                                    </label>

                                    <input
                                        v-model="form.direccion"
                                        type="text"
                                        placeholder="Dirección del paciente"
                                        class="input-clinica"
                                    >

                                    <p
                                        v-if="form.errors.direccion"
                                        class="error-text"
                                    >
                                        {{ form.errors.direccion }}
                                    </p>

                                </div>

                            </div>

                        </section>


                        <!-- EMERGENCIA -->

                        <section
                            class="
                                mt-9
                                border-t
                                border-slate-100
                                pt-7
                            "
                        >

                            <div
                                class="
                                    mb-5
                                    flex
                                    items-center
                                    gap-3
                                "
                            >

                                <div
                                    class="
                                        flex
                                        h-9
                                        w-9
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-rose-50
                                        text-rose-600
                                    "
                                >
                                    <HeartHandshake :size="17" />
                                </div>

                                <div>

                                    <h3
                                        class="
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        Contacto de emergencia
                                    </h3>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                        Información opcional
                                    </p>

                                </div>

                            </div>


                            <div
                                class="
                                    grid
                                    gap-5
                                    sm:grid-cols-2
                                "
                            >

                                <div>

                                    <label class="form-label">
                                        Nombre del contacto
                                    </label>

                                    <input
                                        v-model="
                                            form.nombre_contacto_emergencia
                                        "
                                        type="text"
                                        placeholder="Nombre completo"
                                        class="input-clinica"
                                    >

                                </div>


                                <div>

                                    <label class="form-label">
                                        Teléfono del contacto
                                    </label>

                                    <input
                                        v-model="
                                            form.telefono_contacto_emergencia
                                        "
                                        type="tel"
                                        placeholder="987 654 321"
                                        class="input-clinica"
                                    >

                                </div>

                            </div>

                        </section>


                        <!-- OBSERVACIONES -->

                        <section
                            class="
                                mt-9
                                border-t
                                border-slate-100
                                pt-7
                            "
                        >

                            <label class="form-label">
                                Observaciones
                            </label>

                            <textarea
                                v-model="form.observaciones"
                                rows="4"
                                placeholder="Información adicional del paciente..."
                                class="
                                    input-clinica
                                    min-h-28
                                    resize-y
                                    py-3
                                "
                            ></textarea>


                            <label
                                class="
                                    mt-5
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
                                        rounded
                                        border-slate-300
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
                                        Paciente activo
                                    </p>

                                    <p
                                        class="
                                            mt-0.5
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                        Podrá utilizarse al registrar citas
                                    </p>

                                </div>

                            </label>

                        </section>

                    </div>


                    <!-- FOOTER -->

                    <footer
                        class="
                            flex
                            items-center
                            justify-end
                            gap-3
                            border-t
                            border-slate-200
                            bg-white
                            px-6
                            py-4
                        "
                    >

                        <button
                            type="button"
                            :disabled="form.processing"
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                px-4
                                py-2.5
                                text-sm
                                font-semibold
                                text-slate-700
                                transition
                                hover:bg-slate-50
                                disabled:cursor-not-allowed
                                disabled:opacity-50
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
                                shadow-sm
                                transition
                                hover:bg-clinica-800
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
                                            : 'Guardar paciente'
                                }}

                        </button>

                    </footer>

                </form>

            </aside>

        </Transition>

    </div>

</template>