<script setup>
import {
    computed,
    ref,
    watch,
} from 'vue';

import {
    Head,
    Link,
    router,
    usePage,
} from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import UsuarioFormDrawer
    from '@/components/usuarios/UsuarioFormDrawer.vue';

import {
    Pencil,
    Plus,
    Search,
    SlidersHorizontal,
    UserCog,
    UserRoundCheck,
    UserRoundX,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    usuarios: {
        type: Object,
        required: true,
    },
    profesionales: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
    filtros: {
        type: Object,
        default: () => ({
            buscar: '',
            rol: 'todos',
            estado: 'todos',
        }),
    },
});

const page = usePage();

const permisos = computed(() => new Set(
    page.props.auth?.permissions ?? []
));

const puedeCrear = computed(() =>
    permisos.value.has('usuarios.crear')
);

const puedeEditar = computed(() =>
    permisos.value.has('usuarios.editar')
);

const buscar = ref(props.filtros.buscar ?? '');
const rol = ref(props.filtros.rol ?? 'todos');
const estado = ref(props.filtros.estado ?? 'todos');
const drawerAbierto = ref(false);
const usuarioSeleccionado = ref(null);
const errorAccion = ref('');

let temporizador = null;

watch(buscar, () => {
    clearTimeout(temporizador);

    temporizador = setTimeout(() => {
        aplicarFiltros();
    }, 400);
});

watch([rol, estado], () => {
    aplicarFiltros();
});

function aplicarFiltros() {
    router.get(
        '/clinica/usuarios',
        {
            buscar: buscar.value || undefined,
            rol: rol.value !== 'todos'
                ? rol.value
                : undefined,
            estado: estado.value !== 'todos'
                ? estado.value
                : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

function limpiarBusqueda() {
    buscar.value = '';
}

function abrirNuevo() {
    usuarioSeleccionado.value = null;
    drawerAbierto.value = true;
}

function editarUsuario(usuario) {
    usuarioSeleccionado.value = usuario;
    drawerAbierto.value = true;
}

function cerrarDrawer() {
    drawerAbierto.value = false;
    usuarioSeleccionado.value = null;
}

function desactivar(usuario) {
    if (!confirm(`¿Desactivar a ${usuario.name}?`)) {
        return;
    }

    errorAccion.value = '';

    router.patch(
        `/clinica/usuarios/${usuario.id}/desactivar`,
        {},
        {
            preserveScroll: true,
            onError: (errors) => {
                errorAccion.value = errors.usuario
                    ?? 'No se pudo desactivar el usuario.';
            },
        }
    );
}

function reactivar(usuario) {
    errorAccion.value = '';

    router.patch(
        `/clinica/usuarios/${usuario.id}/reactivar`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                errorAccion.value =
                    'No se pudo reactivar el usuario.';
            },
        }
    );
}

function nombreProfesional(usuario) {
    if (!usuario.profesional) {
        return '—';
    }

    return [
        usuario.profesional.nombres,
        usuario.profesional.apellidos,
    ].filter(Boolean).join(' ');
}

function claseRol(nombreRol) {
    return {
        ADMINISTRADOR: 'bg-violet-50 text-violet-700',
        RECEPCIONISTA: 'bg-sky-50 text-sky-700',
        ODONTOLOGO: 'bg-emerald-50 text-emerald-700',
    }[nombreRol] ?? 'bg-slate-100 text-slate-600';
}
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout
        titulo="Usuarios"
        descripcion="Gestiona accesos, roles y cuentas del sistema"
    >
        <section class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-clinica-50 text-clinica-700">
                    <UserCog :size="21" />
                </div>

                <div>
                    <h2 class="text-xl font-bold text-slate-900">
                        Gestión de usuarios
                    </h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ usuarios.total }} usuarios encontrados
                    </p>
                </div>
            </div>

            <button
                v-if="puedeCrear"
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-clinica-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-clinica-800"
                @click="abrirNuevo"
            >
                <Plus :size="18" />
                Nuevo usuario
            </button>
        </section>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row">
                <div class="relative flex-1">
                    <Search
                        :size="18"
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="buscar"
                        type="search"
                        placeholder="Buscar por nombre o correo..."
                        class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-10 text-sm outline-none focus:border-clinica-400 focus:bg-white focus:ring-4 focus:ring-clinica-50"
                    >
                    <button
                        v-if="buscar"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
                        aria-label="Limpiar búsqueda"
                        @click="limpiarBusqueda"
                    >
                        <X :size="16" />
                    </button>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <div class="hidden h-11 w-11 items-center justify-center rounded-xl border border-slate-200 text-slate-500 lg:flex">
                        <SlidersHorizontal :size="18" />
                    </div>

                    <select
                        v-model="rol"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none"
                    >
                        <option value="todos">
                            Todos los roles
                        </option>
                        <option
                            v-for="nombreRol in roles"
                            :key="nombreRol"
                            :value="nombreRol"
                        >
                            {{ nombreRol }}
                        </option>
                    </select>

                    <select
                        v-model="estado"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none"
                    >
                        <option value="todos">
                            Todos los estados
                        </option>
                        <option value="activos">
                            Activos
                        </option>
                        <option value="inactivos">
                            Inactivos
                        </option>
                    </select>
                </div>
            </div>
        </section>

        <div
            v-if="errorAccion"
            class="mt-4 flex items-start justify-between gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
        >
            <span>{{ errorAccion }}</span>
            <button
                type="button"
                aria-label="Cerrar mensaje"
                @click="errorAccion = ''"
            >
                <X :size="17" />
            </button>
        </div>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3.5">
                                Nombre
                            </th>
                            <th class="px-5 py-3.5">
                                Correo
                            </th>
                            <th class="px-5 py-3.5">
                                Rol
                            </th>
                            <th class="px-5 py-3.5">
                                Profesional asociado
                            </th>
                            <th class="px-5 py-3.5">
                                Estado
                            </th>
                            <th class="px-5 py-3.5 text-right">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="usuario in usuarios.data"
                            :key="usuario.id"
                            class="text-sm text-slate-700 transition hover:bg-slate-50/70"
                        >
                            <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">
                                {{ usuario.name }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                {{ usuario.email }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="claseRol(usuario.rol)"
                                >
                                    {{ usuario.rol ?? 'Sin rol' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                {{ nombreProfesional(usuario) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="usuario.activo
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-slate-100 text-slate-500'"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="usuario.activo
                                            ? 'bg-emerald-500'
                                            : 'bg-slate-400'"
                                    ></span>
                                    {{ usuario.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <div
                                    v-if="puedeEditar"
                                    class="flex justify-end gap-2"
                                >
                                    <button
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-clinica-50 hover:text-clinica-700"
                                        title="Editar usuario"
                                        @click="editarUsuario(usuario)"
                                    >
                                        <Pencil :size="17" />
                                    </button>

                                    <button
                                        v-if="usuario.activo"
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600"
                                        title="Desactivar usuario"
                                        @click="desactivar(usuario)"
                                    >
                                        <UserRoundX :size="18" />
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-emerald-50 hover:text-emerald-700"
                                        title="Reactivar usuario"
                                        @click="reactivar(usuario)"
                                    >
                                        <UserRoundCheck :size="18" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!usuarios.data.length">
                            <td
                                colspan="6"
                                class="px-5 py-14 text-center"
                            >
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <Search :size="21" />
                                </div>
                                <p class="mt-3 font-semibold text-slate-700">
                                    No se encontraron usuarios
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Ajusta la búsqueda o los filtros.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav
                v-if="usuarios.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4"
            >
                <p class="text-sm text-slate-500">
                    Página {{ usuarios.current_page }} de {{ usuarios.last_page }}
                </p>

                <div class="flex flex-wrap gap-1">
                    <template
                        v-for="enlace in usuarios.links"
                        :key="enlace.label"
                    >
                        <Link
                            v-if="enlace.url"
                            :href="enlace.url"
                            preserve-scroll
                            class="min-w-9 rounded-lg border px-3 py-1.5 text-center text-sm transition"
                            :class="enlace.active
                                ? 'border-clinica-700 bg-clinica-700 text-white'
                                : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                        >
                            <span v-html="enlace.label"></span>
                        </Link>
                        <span
                            v-else
                            class="min-w-9 cursor-not-allowed rounded-lg border border-slate-100 px-3 py-1.5 text-center text-sm text-slate-300"
                            v-html="enlace.label"
                        ></span>
                    </template>
                </div>
            </nav>
        </section>

        <UsuarioFormDrawer
            :open="drawerAbierto"
            :usuario="usuarioSeleccionado"
            :profesionales="profesionales"
            :roles="roles"
            @close="cerrarDrawer"
        />
    </AppLayout>
</template>
