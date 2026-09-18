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
} from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

import {
    ClipboardList,
    Eye,
    Search,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    actividades: {
        type: Object,
        required: true,
    },
    usuarios: {
        type: Array,
        default: () => [],
    },
    modulos: {
        type: Array,
        default: () => [],
    },
    acciones: {
        type: Array,
        default: () => [],
    },
    filtros: {
        type: Object,
        default: () => ({}),
    },
});

const buscar = ref(props.filtros.buscar ?? '');
const usuario = ref(props.filtros.usuario ?? '');
const modulo = ref(props.filtros.modulo ?? '');
const accion = ref(props.filtros.accion ?? '');
const desde = ref(props.filtros.desde ?? '');
const hasta = ref(props.filtros.hasta ?? '');
const seleccionada = ref(null);

let temporizador = null;

watch(buscar, () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(aplicarFiltros, 400);
});

watch([usuario, modulo, accion, desde, hasta], aplicarFiltros);

const cambios = computed(() => {
    if (!seleccionada.value) {
        return [];
    }

    const anteriores = seleccionada.value.cambios?.old ?? {};
    const nuevos = seleccionada.value.cambios?.new ?? {};

    return [...new Set([
        ...Object.keys(anteriores),
        ...Object.keys(nuevos),
    ])].map((campo) => ({
        campo,
        anterior: anteriores[campo],
        nuevo: nuevos[campo],
    }));
});

const etiquetasCampos = {
    activo: 'Estado activo',
    cita_origen_id: 'Cita de origen',
    consultorio_id: 'Consultorio',
    correo: 'Correo',
    email: 'Correo',
    estado: 'Estado',
    estado_cita_id: 'Estado de cita',
    fecha_anulacion: 'Fecha de anulación',
    fecha_cancelacion: 'Fecha de cancelación',
    fecha_fin: 'Fecha de fin',
    fecha_hora_fin: 'Fin programado',
    fecha_hora_fin_real: 'Fin real',
    fecha_hora_inicio: 'Inicio programado',
    fecha_inicio: 'Fecha de inicio',
    fecha_nacimiento: 'Fecha de nacimiento',
    fecha_pago: 'Fecha de pago',
    metodo_pago_id: 'Método de pago',
    monto: 'Monto',
    name: 'Nombre',
    nombres: 'Nombres',
    apellidos: 'Apellidos',
    numero_documento: 'Documento',
    numero_operacion: 'Número de operación',
    paciente_id: 'Paciente',
    profesional_id: 'Profesional',
    rol: 'Rol',
    servicio_id: 'Servicio',
    telefono: 'Teléfono',
    tratamiento_paciente_id: 'Tratamiento',
    usuario_anulacion_id: 'Usuario de anulación',
    usuario_registro_id: 'Usuario de registro',
};

function aplicarFiltros() {
    router.get('/clinica/auditoria', {
        buscar: buscar.value || undefined,
        usuario: usuario.value || undefined,
        modulo: modulo.value || undefined,
        accion: accion.value || undefined,
        desde: desde.value || undefined,
        hasta: hasta.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function limpiarFiltros() {
    buscar.value = '';
    usuario.value = '';
    modulo.value = '';
    accion.value = '';
    desde.value = '';
    hasta.value = '';
}

function titulo(texto) {
    return String(texto ?? '—')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letra) => letra.toUpperCase());
}

function fechaHora(valor) {
    if (!valor) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-PE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(valor));
}

function mostrarValor(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return '—';
    }

    if (valor === true) {
        return 'Sí';
    }

    if (valor === false) {
        return 'No';
    }

    return typeof valor === 'object'
        ? JSON.stringify(valor)
        : String(valor);
}
</script>

<template>
    <Head title="Auditoría" />

    <AppLayout
        titulo="Auditoría"
        descripcion="Trazabilidad de acciones importantes del sistema"
    >
        <section class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-clinica-50 text-clinica-700">
                <ClipboardList :size="21" />
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Registro de auditoría
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ actividades.total }} actividades encontradas
                </p>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="relative md:col-span-2">
                    <Search :size="18" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="buscar"
                        type="search"
                        placeholder="Buscar usuario, acción o registro..."
                        class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm outline-none focus:border-clinica-400 focus:bg-white focus:ring-4 focus:ring-clinica-50"
                    >
                </div>

                <select v-model="usuario" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                    <option value="">Todos los usuarios</option>
                    <option v-for="item in usuarios" :key="item.id" :value="item.id">
                        {{ item.name }}
                    </option>
                </select>

                <select v-model="modulo" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                    <option value="">Todos los módulos</option>
                    <option v-for="item in modulos" :key="item" :value="item">
                        {{ titulo(item) }}
                    </option>
                </select>

                <select v-model="accion" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                    <option value="">Todas las acciones</option>
                    <option v-for="item in acciones" :key="item" :value="item">
                        {{ titulo(item) }}
                    </option>
                </select>

                <input v-model="desde" type="date" aria-label="Desde" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                <input v-model="hasta" type="date" aria-label="Hasta" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">

                <button type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="limpiarFiltros">
                    <X :size="17" />
                    Limpiar filtros
                </button>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Usuario</th>
                            <th class="px-5 py-3">Módulo</th>
                            <th class="px-5 py-3">Acción</th>
                            <th class="px-5 py-3">Registro</th>
                            <th class="px-5 py-3 text-right">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="actividad in actividades.data" :key="actividad.id" class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ fechaHora(actividad.fecha) }}</td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-800">{{ actividad.usuario?.name ?? 'Sistema / anónimo' }}</p>
                                <p class="text-xs text-slate-500">{{ actividad.usuario?.email }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ titulo(actividad.modulo) }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-clinica-50 px-2.5 py-1 text-xs font-semibold text-clinica-700">{{ titulo(actividad.accion) }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ actividad.modelo ?? '—' }}<span v-if="actividad.registro_id"> #{{ actividad.registro_id }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 font-semibold text-clinica-700 hover:bg-clinica-50" @click="seleccionada = actividad">
                                    <Eye :size="16" /> Ver
                                </button>
                            </td>
                        </tr>
                        <tr v-if="actividades.data.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">No hay actividades para los filtros seleccionados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav v-if="actividades.links?.length > 3" class="flex flex-wrap justify-center gap-1 border-t border-slate-100 p-4">
                <Link
                    v-for="enlace in actividades.links"
                    :key="enlace.label"
                    :href="enlace.url ?? ''"
                    preserve-scroll
                    preserve-state
                    class="rounded-lg border px-3 py-2 text-sm"
                    :class="enlace.active ? 'border-clinica-600 bg-clinica-600 text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                    :aria-disabled="!enlace.url"
                    v-html="enlace.label"
                />
            </nav>
        </section>

        <div v-if="seleccionada" class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/45 p-4" @click.self="seleccionada = null">
            <section class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-100 p-5">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">{{ seleccionada.descripcion }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ fechaHora(seleccionada.fecha) }}</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="seleccionada = null"><X :size="19" /></button>
                </header>

                <div class="space-y-6 p-5">
                    <dl class="grid gap-4 rounded-xl bg-slate-50 p-4 sm:grid-cols-2">
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Usuario</dt><dd class="mt-1 text-sm text-slate-800">{{ seleccionada.usuario?.name ?? 'Sistema / anónimo' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Acción</dt><dd class="mt-1 text-sm text-slate-800">{{ titulo(seleccionada.accion) }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Módulo</dt><dd class="mt-1 text-sm text-slate-800">{{ titulo(seleccionada.modulo) }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Registro</dt><dd class="mt-1 text-sm text-slate-800">{{ seleccionada.modelo ?? '—' }}<span v-if="seleccionada.registro_id"> #{{ seleccionada.registro_id }}</span></dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">IP</dt><dd class="mt-1 text-sm text-slate-800">{{ seleccionada.propiedades?.ip ?? '—' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Navegador</dt><dd class="mt-1 break-words text-sm text-slate-800">{{ seleccionada.propiedades?.user_agent ?? '—' }}</dd></div>
                    </dl>

                    <div>
                        <h4 class="mb-3 font-semibold text-slate-900">Cambios</h4>
                        <div v-if="cambios.length" class="overflow-hidden rounded-xl border border-slate-200">
                            <div v-for="cambio in cambios" :key="cambio.campo" class="grid gap-2 border-b border-slate-100 p-3 last:border-0 sm:grid-cols-3">
                                <span class="text-sm font-semibold text-slate-700">{{ etiquetasCampos[cambio.campo] ?? titulo(cambio.campo) }}</span>
                                <span class="text-sm text-rose-700"><span class="mr-1 text-xs text-slate-400">Antes:</span>{{ mostrarValor(cambio.anterior) }}</span>
                                <span class="text-sm text-emerald-700"><span class="mr-1 text-xs text-slate-400">Después:</span>{{ mostrarValor(cambio.nuevo) }}</span>
                            </div>
                        </div>
                        <p v-else class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-500">Esta actividad no modificó atributos del registro.</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
