<script setup>
import {
    computed,
    ref,
} from 'vue';

import {
    router,
    usePage,
} from '@inertiajs/vue3';

const page = usePage();
const cerrandoSesion = ref(false);

const usuario = computed(() =>
    page.props.auth?.user ?? null
);

const permisos = computed(() => new Set(
    page.props.auth?.permissions ?? []
));

const roles = computed(() => new Set(
    page.props.auth?.roles ?? []
));

const iniciales = computed(() => {
    const partes = usuario.value?.name
        ?.trim()
        .split(/\s+/)
        .filter(Boolean) ?? [];

    return partes
        .slice(0, 2)
        .map((parte) => parte[0])
        .join('')
        .toUpperCase() || 'CC';
});


import {
    LayoutDashboard,
    Users,
    UserCog,
    CalendarDays,
    Stethoscope,
    DoorOpen,
    HeartPulse,
    CreditCard,
    Settings,
    X,
    LogOut,
    Smile,
    ClipboardList,
} from 'lucide-vue-next';

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    'close',
]);

const principal = [
    {
        nombre: 'Dashboard',
        href: '/clinica',
        icono: LayoutDashboard,
        disponible: true,
        permiso: 'dashboard.ver',
    },
    {
        nombre: 'Pacientes',
        href: '/clinica/pacientes',
        icono: Users,
        disponible: true,
        permiso: 'pacientes.ver',
    },
    {
        nombre: 'Citas',
        href: '/clinica/citas',
        icono: CalendarDays,
        disponible: true,
        permiso: 'citas.ver',
    },
    {
        nombre: 'Profesionales',
        href: '/clinica/profesionales',
        icono: Stethoscope,
        disponible: true,
        permiso: 'profesionales.ver',
    },
];

const administracion = [
    {
        nombre: 'Consultorios',
        href: '/clinica/consultorios',
        icono: DoorOpen,
        disponible: true,
        permiso: 'consultorios.ver',
    },
    {
        nombre: 'Servicios',
        href: '/clinica/servicios',
        icono: HeartPulse,
        disponible: true,
        permiso: 'servicios.ver',
    },
    {
        nombre: 'Pagos',
        href: '/clinica/pagos',
        icono: CreditCard,
        disponible: true,
        permiso: 'pagos.ver',
    },
    {
        nombre: 'Usuarios',
        href: '/clinica/usuarios',
        icono: UserCog,
        disponible: true,
        permiso: 'usuarios.ver',
    },
    {
        nombre: 'Auditoría',
        href: '/clinica/auditoria',
        icono: ClipboardList,
        disponible: true,
        permiso: 'auditoria.ver',
    },
    {
        nombre: 'Configuración',
        href: '/clinica/configuracion',
        icono: Settings,
        disponible: false,
        rol: 'ADMINISTRADOR',
    },
];

function puedeVer(item) {
    if (item.rol) {
        return roles.value.has(item.rol);
    }

    return !item.permiso
        || permisos.value.has(item.permiso);
}

const principalVisible = computed(() =>
    principal.filter(puedeVer)
);

const administracionVisible = computed(() =>
    administracion.filter(puedeVer)
);

function activo(href) {

    const rutaActual =
        page.url.split('?')[0];

    if (href === '/clinica') {
        return rutaActual === '/clinica';
    }

    return rutaActual.startsWith(href);
}

function cerrarSesion() {
    if (cerrandoSesion.value) {
        return;
    }

    cerrandoSesion.value = true;

    router.post('/logout', {}, {
        onFinish: () => {
            cerrandoSesion.value = false;
        },
    });
}
</script>

<template>
    <aside
        class="
            fixed
            inset-y-0
            left-0
            z-50
            flex
            w-72
            flex-col
            border-r
            border-slate-200
            bg-white
            transition-transform
            duration-300
            lg:translate-x-0
        "
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- LOGO -->

        <div
            class="
                flex
                h-20
                items-center
                justify-between
                border-b
                border-slate-100
                px-6
            "
        >
            <div class="flex min-w-0 items-center">

                <img
                    src="/images/logo-clinica.svg"
                    alt="Clínica Cabanillas"
                    class="
                        h-19
                        m-5
                        w-auto
                        max-w-[190px]
                        object-contain
                    "
                >

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
                    text-slate-500
                    transition
                    hover:bg-slate-100
                    lg:hidden
                "
                @click="emit('close')"
            >
                <X :size="19" />
            </button>
        </div>

        <!-- NAVEGACIÓN -->

        <nav
            class="
                flex-1
                overflow-y-auto
                px-4
                py-6
            "
        >

            <p
                class="
                    mb-2
                    px-3
                    text-[11px]
                    font-bold
                    uppercase
                    tracking-[0.16em]
                    text-slate-400
                "
            >
                Principal
            </p>

            <div class="space-y-1">

                <template
                    v-for="item in principalVisible"
                    :key="item.nombre"
                >

                    <a
                        v-if="item.disponible"
                        :href="item.href"
                        class="
                            flex
                            items-center
                            gap-3
                            rounded-xl
                            px-3
                            py-2.5
                            text-sm
                            font-medium
                            transition
                        "
                        :class="
                            activo(item.href)
                                ? 'bg-clinica-50 text-clinica-800'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                        "
                    >
                        <component
                            :is="item.icono"
                            :size="19"
                        />

                        {{ item.nombre }}
                    </a>

                    <div
                        v-else
                        class="
                            group
                            flex
                            cursor-not-allowed
                            items-center
                            gap-3
                            rounded-xl
                            px-3
                            py-2.5
                            text-sm
                            font-medium
                            text-slate-400
                        "
                    >
                        <component
                            :is="item.icono"
                            :size="19"
                        />

                        <span>
                            {{ item.nombre }}
                        </span>

                        <span
                            class="
                                ml-auto
                                rounded-full
                                bg-slate-100
                                px-2
                                py-0.5
                                text-[9px]
                                font-semibold
                                text-slate-400
                            "
                        >
                            Próximamente
                        </span>
                    </div>

                </template>

            </div>


            <p
                v-if="administracionVisible.length"
                class="
                    mb-2
                    mt-8
                    px-3
                    text-[11px]
                    font-bold
                    uppercase
                    tracking-[0.16em]
                    text-slate-400
                "
            >
                Administración
            </p>

            <div
                v-if="administracionVisible.length"
                class="space-y-1"
            >

                <template
                    v-for="item in administracionVisible"
                    :key="item.nombre"
                >

                    <!-- DISPONIBLE -->

                    <a
                        v-if="item.disponible"
                        :href="item.href"
                        class="
                            flex
                            items-center
                            gap-3
                            rounded-xl
                            px-3
                            py-2.5
                            text-sm
                            font-medium
                            transition
                        "
                        :class="
                            activo(item.href)
                                ? 'bg-clinica-50 text-clinica-800'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                        "
                    >
                        <component
                            :is="item.icono"
                            :size="19"
                        />

                        {{ item.nombre }}
                    </a>


                    <!-- BLOQUEADO -->

                    <div
                        v-else
                        class="
                            flex
                            cursor-not-allowed
                            items-center
                            gap-3
                            rounded-xl
                            px-3
                            py-2.5
                            text-sm
                            font-medium
                            text-slate-400
                        "
                    >
                        <component
                            :is="item.icono"
                            :size="19"
                        />

                        <span>
                            {{ item.nombre }}
                        </span>

                        <span
                            class="
                                ml-auto
                                rounded-full
                                bg-slate-100
                                px-2
                                py-0.5
                                text-[9px]
                                font-semibold
                                text-slate-400
                            "
                        >
                            Próximamente
                        </span>
                    </div>

                </template>

            </div>

        </nav>


        <!-- USUARIO -->

        <div
            class="
                border-t
                border-slate-100
                p-4
            "
        >
            <div
                class="
                    flex
                    items-center
                    gap-3
                    rounded-2xl
                    bg-slate-50
                    p-3
                "
            >

                <div
                    class="
                        flex
                        h-10
                        w-10
                        items-center
                        justify-center
                        rounded-xl
                        bg-clinica-700
                        text-sm
                        font-bold
                        text-white
                    "
                >
                    {{ iniciales }}
                </div>

                <div class="min-w-0">

                    <p
                        class="
                            truncate
                            text-sm
                            font-semibold
                            text-slate-800
                        "
                    >
                        {{ usuario?.name }}
                    </p>

                    <p
                        class="
                            truncate
                            text-xs
                            text-slate-500
                        "
                    >
                        {{ usuario?.email }}
                    </p>

                </div>

            </div>
            <button
                type="button"
                :disabled="cerrandoSesion"
                class="
                    mt-3
                    flex
                    w-full
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    px-3
                    py-2
                    text-xs
                    font-semibold
                    text-slate-600
                    transition
                    hover:border-rose-200
                    hover:bg-rose-50
                    hover:text-rose-700
                    disabled:cursor-not-allowed
                    disabled:opacity-60
                "
                @click="cerrarSesion"
            >
                <LogOut :size="15" />

                {{
                    cerrandoSesion
                        ? 'Cerrando sesión...'
                        : 'Cerrar sesión'
                }}
            </button>

        </div>

    </aside>
</template>
