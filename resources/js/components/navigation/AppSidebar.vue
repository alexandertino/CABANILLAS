<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();


import {
    LayoutDashboard,
    Users,
    CalendarDays,
    Stethoscope,
    DoorOpen,
    HeartPulse,
    CreditCard,
    Settings,
    X,
    Smile,
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
    },
    {
        nombre: 'Pacientes',
        href: '/clinica/pacientes',
        icono: Users,
        disponible: true,
    },
    {
        nombre: 'Citas',
        href: '/clinica/citas',
        icono: CalendarDays,
        disponible: true,
    },
    {
        nombre: 'Profesionales',
        href: '/clinica/profesionales',
        icono: Stethoscope,
        disponible: true,
    },
];

const administracion = [
    {
        nombre: 'Consultorios',
        href: '/clinica/consultorios',
        icono: DoorOpen,
        disponible: true,
    },
    {
        nombre: 'Servicios',
        href: '/clinica/servicios',
        icono: HeartPulse,
        disponible: true,
    },
    {
        nombre: 'Pagos',
        href: '/clinica/pagos',
        icono: CreditCard,
        disponible: false,
    },
    {
        nombre: 'Configuración',
        href: '/clinica/configuracion',
        icono: Settings,
        disponible: false,
    },
];

function activo(href) {

    const rutaActual =
        page.url.split('?')[0];

    if (href === '/clinica') {
        return rutaActual === '/clinica';
    }

    return rutaActual.startsWith(href);
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
                    v-for="item in principal"
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

            <div class="space-y-1">

                <template
                    v-for="item in administracion"
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
                    AD
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
                        Administrador
                    </p>

                    <p
                        class="
                            truncate
                            text-xs
                            text-slate-500
                        "
                    >
                        Clínica Cabanillas
                    </p>

                </div>

            </div>
        </div>

    </aside>
</template>