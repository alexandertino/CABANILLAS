<script setup>
import {
    computed,
    watch,
} from 'vue';

import { useForm } from '@inertiajs/vue3';

import {
    KeyRound,
    LoaderCircle,
    Save,
    UserCog,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    usuario: {
        type: Object,
        default: null,
    },
    profesionales: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    'close',
]);

const form = useForm({
    name: '',
    email: '',
    rol: 'RECEPCIONISTA',
    profesional_id: null,
    password: '',
    password_confirmation: '',
});

const editando = computed(() =>
    Boolean(props.usuario?.id)
);

const esOdontologo = computed(() =>
    form.rol === 'ODONTOLOGO'
);

const profesionalesDisponibles = computed(() =>
    props.profesionales.filter((profesional) =>
        !profesional.usuario_id
        || profesional.usuario_id === props.usuario?.id
    )
);

function cargar() {
    form.reset();
    form.clearErrors();

    if (!props.usuario) {
        form.rol = 'RECEPCIONISTA';
        form.profesional_id = null;

        return;
    }

    form.name = props.usuario.name ?? '';
    form.email = props.usuario.email ?? '';
    form.rol = props.usuario.rol ?? 'RECEPCIONISTA';
    form.profesional_id = props.usuario.profesional_id ?? null;
    form.password = '';
    form.password_confirmation = '';
}

watch(
    [
        () => props.open,
        () => props.usuario,
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

watch(
    () => form.rol,
    (rol) => {
        if (rol !== 'ODONTOLOGO') {
            form.profesional_id = null;
            form.clearErrors('profesional_id');
        }
    }
);

function cerrar() {
    if (!form.processing) {
        emit('close');
    }
}

function guardar() {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    };

    if (editando.value) {
        form.put(
            `/clinica/usuarios/${props.usuario.id}`,
            opciones
        );

        return;
    }

    form.post('/clinica/usuarios', opciones);
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
                class="fixed inset-0 z-[90] bg-slate-950/40 backdrop-blur-[2px]"
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
                class="fixed inset-y-0 right-0 z-[100] flex w-full max-w-xl flex-col border-l border-slate-200 bg-white shadow-2xl"
            >
                <header class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-clinica-50 text-clinica-700">
                            <UserCog :size="21" />
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                {{ editando ? 'Editar usuario' : 'Nuevo usuario' }}
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Acceso y rol dentro del sistema
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100"
                        aria-label="Cerrar formulario"
                        @click="cerrar"
                    >
                        <X :size="20" />
                    </button>
                </header>

                <form
                    class="flex min-h-0 flex-1 flex-col"
                    @submit.prevent="guardar"
                >
                    <div class="flex-1 space-y-5 overflow-y-auto p-6">
                        <div>
                            <label class="form-label">
                                Nombre completo
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                class="input-clinica"
                                placeholder="Nombre del usuario"
                            >
                            <p
                                v-if="form.errors.name"
                                class="error-text"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="form-label">
                                Correo electrónico
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                class="input-clinica"
                                placeholder="usuario@cabanillas.com"
                            >
                            <p
                                v-if="form.errors.email"
                                class="error-text"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label class="form-label">
                                Rol
                            </label>
                            <select
                                v-model="form.rol"
                                class="input-clinica"
                            >
                                <option
                                    v-for="rol in roles"
                                    :key="rol"
                                    :value="rol"
                                >
                                    {{ rol }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.rol"
                                class="error-text"
                            >
                                {{ form.errors.rol }}
                            </p>
                        </div>

                        <div v-if="esOdontologo">
                            <label class="form-label">
                                Profesional asociado
                            </label>
                            <select
                                v-model="form.profesional_id"
                                class="input-clinica"
                            >
                                <option :value="null">
                                    Selecciona un profesional
                                </option>
                                <option
                                    v-for="profesional in profesionalesDisponibles"
                                    :key="profesional.id"
                                    :value="profesional.id"
                                >
                                    {{ profesional.nombres }}
                                    {{ profesional.apellidos }}
                                </option>
                            </select>
                            <p
                                v-if="!profesionalesDisponibles.length"
                                class="mt-2 text-xs text-amber-700"
                            >
                                No hay profesionales activos disponibles.
                            </p>
                            <p
                                v-if="form.errors.profesional_id"
                                class="error-text"
                            >
                                {{ form.errors.profesional_id }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <KeyRound :size="17" />
                                {{ editando ? 'Cambiar contraseña' : 'Contraseña inicial' }}
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="form-label">
                                        {{ editando ? 'Nueva contraseña' : 'Contraseña' }}
                                    </label>
                                    <input
                                        v-model="form.password"
                                        type="password"
                                        :autocomplete="editando ? 'new-password' : 'new-password'"
                                        class="input-clinica"
                                        :placeholder="editando ? 'Dejar vacío para conservar' : 'Mínimo 8 caracteres'"
                                    >
                                    <p
                                        v-if="form.errors.password"
                                        class="error-text"
                                    >
                                        {{ form.errors.password }}
                                    </p>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Confirmar contraseña
                                    </label>
                                    <input
                                        v-model="form.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        class="input-clinica"
                                        placeholder="Repite la contraseña"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <footer class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                            :disabled="form.processing"
                            @click="cerrar"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-clinica-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-clinica-800 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <LoaderCircle
                                v-if="form.processing"
                                :size="18"
                                class="animate-spin"
                            />
                            <Save
                                v-else
                                :size="18"
                            />
                            {{ form.processing ? 'Guardando...' : 'Guardar usuario' }}
                        </button>
                    </footer>
                </form>
            </aside>
        </Transition>
    </div>
</template>
