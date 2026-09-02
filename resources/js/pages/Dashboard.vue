<script setup>
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

import {
    Users,
    CalendarDays,
    WalletCards,
    Activity,
    ArrowUpRight,
    CalendarPlus,
    UserPlus,
    CreditCard,
    Stethoscope,
} from 'lucide-vue-next';

const estadisticas = [
    {
        titulo: 'Pacientes registrados',
        valor: '0',
        descripcion: 'Total de pacientes',
        icono: Users,
    },
    {
        titulo: 'Citas de hoy',
        valor: '0',
        descripcion: 'Programadas para hoy',
        icono: CalendarDays,
    },
    {
        titulo: 'Ingresos del mes',
        valor: 'S/ 0.00',
        descripcion: 'Pagos registrados',
        icono: WalletCards,
    },
    {
        titulo: 'En atención',
        valor: '0',
        descripcion: 'Pacientes actualmente',
        icono: Activity,
    },
];
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout
        titulo="Dashboard"
        descripcion="Resumen general de Clínica Cabanillas"
    >

        <!-- BIENVENIDA -->

        <section
            class="
                relative
                overflow-hidden
                rounded-3xl
                bg-gradient-to-br
                from-clinica-700
                via-clinica-700
                to-clinica-900
                px-7
                py-8
                text-white
                shadow-sm
                lg:px-10
            "
        >

            <div
                class="
                    absolute
                    -right-20
                    -top-20
                    h-72
                    w-72
                    rounded-full
                    bg-white/10
                "
            ></div>

            <div
                class="
                    absolute
                    -bottom-32
                    right-32
                    h-72
                    w-72
                    rounded-full
                    bg-cyan-300/10
                "
            ></div>


            <div class="relative z-10 max-w-2xl">

                <div
                    class="
                        mb-4
                        inline-flex
                        items-center
                        rounded-full
                        border
                        border-white/15
                        bg-white/10
                        px-3
                        py-1
                        text-xs
                        font-semibold
                        backdrop-blur
                    "
                >
                    Sistema de gestión odontológica
                </div>

                <h2
                    class="
                        text-2xl
                        font-bold
                        tracking-tight
                        sm:text-3xl
                    "
                >
                    Bienvenido a Clínica Cabanillas
                </h2>

                <p
                    class="
                        mt-3
                        max-w-xl
                        text-sm
                        leading-6
                        text-teal-50/80
                    "
                >
                    Administra pacientes, citas, servicios y pagos
                    desde un único sistema organizado.
                </p>

            </div>

        </section>


        <!-- ESTADÍSTICAS -->

        <section
            class="
                mt-6
                grid
                gap-4
                sm:grid-cols-2
                xl:grid-cols-4
            "
        >

            <article
                v-for="estadistica in estadisticas"
                :key="estadistica.titulo"
                class="
                    group
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    shadow-sm
                    transition
                    duration-200
                    hover:-translate-y-0.5
                    hover:shadow-md
                "
            >

                <div
                    class="
                        flex
                        items-start
                        justify-between
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
                            bg-clinica-50
                            text-clinica-700
                        "
                    >
                        <component
                            :is="estadistica.icono"
                            :size="21"
                        />
                    </div>

                    <ArrowUpRight
                        :size="17"
                        class="
                            text-slate-300
                            transition
                            group-hover:text-clinica-600
                        "
                    />

                </div>

                <p
                    class="
                        mt-5
                        text-2xl
                        font-bold
                        tracking-tight
                        text-slate-900
                    "
                >
                    {{ estadistica.valor }}
                </p>

                <p
                    class="
                        mt-1
                        text-sm
                        font-medium
                        text-slate-700
                    "
                >
                    {{ estadistica.titulo }}
                </p>

                <p
                    class="
                        mt-1
                        text-xs
                        text-slate-400
                    "
                >
                    {{ estadistica.descripcion }}
                </p>

            </article>

        </section>


        <!-- SEGUNDA FILA -->

        <section
            class="
                mt-6
                grid
                gap-6
                xl:grid-cols-[1.5fr_1fr]
            "
        >

            <!-- CITAS -->

            <div
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    shadow-sm
                "
            >

                <div
                    class="
                        flex
                        items-center
                        justify-between
                        border-b
                        border-slate-100
                        px-6
                        py-5
                    "
                >

                    <div>
                        <h3
                            class="
                                font-bold
                                text-slate-900
                            "
                        >
                            Próximas citas
                        </h3>

                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-500
                            "
                        >
                            Agenda del día
                        </p>
                    </div>

                    <button
                        class="
                            text-sm
                            font-semibold
                            text-clinica-700
                        "
                    >
                        Ver agenda
                    </button>

                </div>


                <!-- VACÍO -->

                <div
                    class="
                        flex
                        min-h-64
                        flex-col
                        items-center
                        justify-center
                        px-6
                        text-center
                    "
                >

                    <div
                        class="
                            flex
                            h-14
                            w-14
                            items-center
                            justify-center
                            rounded-2xl
                            bg-slate-100
                            text-slate-400
                        "
                    >
                        <CalendarDays :size="25" />
                    </div>

                    <h4
                        class="
                            mt-4
                            text-sm
                            font-semibold
                            text-slate-800
                        "
                    >
                        Todavía no hay citas cargadas
                    </h4>

                    <p
                        class="
                            mt-1
                            max-w-xs
                            text-xs
                            leading-5
                            text-slate-500
                        "
                    >
                        Cuando conectemos el módulo de citas,
                        aquí aparecerán las próximas atenciones.
                    </p>

                </div>

            </div>


            <!-- ACCIONES RÁPIDAS -->

            <div
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                    shadow-sm
                "
            >

                <div>
                    <h3
                        class="
                            font-bold
                            text-slate-900
                        "
                    >
                        Acciones rápidas
                    </h3>

                    <p
                        class="
                            mt-1
                            text-xs
                            text-slate-500
                        "
                    >
                        Operaciones frecuentes
                    </p>
                </div>


                <div
                    class="
                        mt-5
                        grid
                        gap-3
                        sm:grid-cols-2
                        xl:grid-cols-1
                    "
                >

                    <button
                        disabled
                        class="
                            flex
                            items-center
                            gap-4
                            rounded-xl
                            border
                            border-slate-200
                            p-4
                            text-left
                            opacity-60
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
                                bg-clinica-50
                                text-clinica-700
                            "
                        >
                            <UserPlus :size="19" />
                        </div>

                        <div>
                            <p
                                class="
                                    text-sm
                                    font-semibold
                                    text-slate-800
                                "
                            >
                                Nuevo paciente
                            </p>

                            <p
                                class="
                                    text-xs
                                    text-slate-400
                                "
                            >
                                Próximamente
                            </p>
                        </div>
                    </button>


                    <button
                        disabled
                        class="
                            flex
                            items-center
                            gap-4
                            rounded-xl
                            border
                            border-slate-200
                            p-4
                            text-left
                            opacity-60
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
                                bg-blue-50
                                text-blue-600
                            "
                        >
                            <CalendarPlus :size="19" />
                        </div>

                        <div>
                            <p
                                class="
                                    text-sm
                                    font-semibold
                                    text-slate-800
                                "
                            >
                                Nueva cita
                            </p>

                            <p class="text-xs text-slate-400">
                                Próximamente
                            </p>
                        </div>
                    </button>


                    <button
                        disabled
                        class="
                            flex
                            items-center
                            gap-4
                            rounded-xl
                            border
                            border-slate-200
                            p-4
                            text-left
                            opacity-60
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
                                bg-emerald-50
                                text-emerald-600
                            "
                        >
                            <CreditCard :size="19" />
                        </div>

                        <div>
                            <p
                                class="
                                    text-sm
                                    font-semibold
                                    text-slate-800
                                "
                            >
                                Registrar pago
                            </p>

                            <p class="text-xs text-slate-400">
                                Próximamente
                            </p>
                        </div>
                    </button>


                    <button
                        disabled
                        class="
                            flex
                            items-center
                            gap-4
                            rounded-xl
                            border
                            border-slate-200
                            p-4
                            text-left
                            opacity-60
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
                                bg-violet-50
                                text-violet-600
                            "
                        >
                            <Stethoscope :size="19" />
                        </div>

                        <div>
                            <p
                                class="
                                    text-sm
                                    font-semibold
                                    text-slate-800
                                "
                            >
                                Profesionales
                            </p>

                            <p class="text-xs text-slate-400">
                                Próximamente
                            </p>
                        </div>
                    </button>

                </div>

            </div>

        </section>

    </AppLayout>

</template>