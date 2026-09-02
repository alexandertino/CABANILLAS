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

import ProfesionalFormDrawer
    from '@/components/profesionales/ProfesionalFormDrawer.vue';

import {
    Search,
    Plus,
    Stethoscope,
    Phone,
    Mail,
    BadgeCheck,
    SlidersHorizontal,
    X,
    Pencil,
} from 'lucide-vue-next';


const props = defineProps({

    profesionales: {
        type: Object,
        required: true,
    },

    filtros: {
        type: Object,
        default: () => ({
            buscar: '',
            estado: 'todos',
        }),
    },

});


const buscar = ref(
    props.filtros.buscar ?? ''
);

const estado = ref(
    props.filtros.estado ?? 'todos'
);

const drawerAbierto = ref(false);

const profesionalSeleccionado = ref(null);

let temporizador = null;


watch(buscar, () => {

    clearTimeout(temporizador);

    temporizador = setTimeout(() => {
        aplicarFiltros();
    }, 400);

});


watch(estado, () => {
    aplicarFiltros();
});


function aplicarFiltros() {

    router.get(
        '/clinica/profesionales',

        {
            buscar:
                buscar.value || undefined,

            estado:
                estado.value !== 'todos'
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

    profesionalSeleccionado.value = null;

    drawerAbierto.value = true;
}


function editarProfesional(profesional) {

    profesionalSeleccionado.value =
        profesional;

    drawerAbierto.value = true;
}


function cerrarDrawer() {

    drawerAbierto.value = false;

    profesionalSeleccionado.value = null;
}


function desactivar(profesional) {

    if (
        !confirm(
            `¿Desactivar a ${profesional.nombres} ${profesional.apellidos}?`
        )
    ) {
        return;
    }

    router.patch(
        `/clinica/profesionales/${profesional.id}/desactivar`,
        {},
        {
            preserveScroll: true,
        }
    );
}


function reactivar(profesional) {

    router.patch(
        `/clinica/profesionales/${profesional.id}/reactivar`,
        {},
        {
            preserveScroll: true,
        }
    );
}


const hayProfesionales = computed(() =>
    props.profesionales.data.length > 0
);


function iniciales(profesional) {

    const nombre =
        profesional.nombres
            ?.trim()
            .charAt(0) ?? '';

    const apellido =
        profesional.apellidos
            ?.trim()
            .charAt(0) ?? '';

    return `${nombre}${apellido}`.toUpperCase();
}
</script>


<template>

    <Head title="Profesionales" />


    <AppLayout
        titulo="Profesionales"
        descripcion="Gestiona los odontólogos y profesionales de la clínica"
    >

        <!-- CABECERA -->

        <section
            class="
                flex
                flex-col
                gap-5
                sm:flex-row
                sm:items-center
                sm:justify-between
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
                            text-xl
                            font-bold
                            text-slate-900
                        "
                    >
                        Equipo profesional
                    </h2>

                    <p
                        class="
                            mt-0.5
                            text-sm
                            text-slate-500
                        "
                    >
                        {{ profesionales.total }}
                        profesionales encontrados
                    </p>

                </div>

            </div>


            <button
                type="button"
                class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    bg-clinica-700
                    px-4
                    py-2.5
                    text-sm
                    font-semibold
                    text-white
                    transition
                    hover:bg-clinica-800
                "
                @click="abrirNuevo"
            >
                <Plus :size="18" />

                Nuevo profesional
            </button>

        </section>


        <!-- FILTROS -->

        <section
            class="
                mt-6
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-4
                shadow-sm
            "
        >

            <div
                class="
                    flex
                    flex-col
                    gap-3
                    lg:flex-row
                "
            >

                <div class="relative flex-1">

                    <Search
                        :size="18"
                        class="
                            absolute
                            left-3.5
                            top-1/2
                            -translate-y-1/2
                            text-slate-400
                        "
                    />

                    <input
                        v-model="buscar"
                        type="search"
                        placeholder="Buscar por nombre, documento o colegiatura..."
                        class="
                            h-11
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            bg-slate-50
                            pl-10
                            pr-10
                            text-sm
                            outline-none
                            focus:border-clinica-400
                            focus:bg-white
                            focus:ring-4
                            focus:ring-clinica-50
                        "
                    >

                    <button
                        v-if="buscar"
                        type="button"
                        class="
                            absolute
                            right-3
                            top-1/2
                            -translate-y-1/2
                            text-slate-400
                        "
                        @click="limpiarBusqueda"
                    >
                        <X :size="16" />
                    </button>

                </div>


                <div
                    class="
                        flex
                        items-center
                        gap-2
                    "
                >

                    <div
                        class="
                            hidden
                            h-11
                            w-11
                            items-center
                            justify-center
                            rounded-xl
                            border
                            border-slate-200
                            text-slate-500
                            sm:flex
                        "
                    >
                        <SlidersHorizontal :size="18" />
                    </div>

                    <select
                        v-model="estado"
                        class="
                            h-11
                            min-w-44
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-3
                            text-sm
                            font-medium
                            text-slate-700
                            outline-none
                        "
                    >
                        <option value="todos">
                            Todos
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


        <!-- TABLA -->

        <section
            class="
                mt-5
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                shadow-sm
            "
        >

            <div
                class="
                    hidden
                    overflow-x-auto
                    md:block
                "
            >

                <table class="w-full text-left">

                    <thead
                        class="
                            border-b
                            border-slate-200
                            bg-slate-50
                        "
                    >

                        <tr>

                            <th class="px-6 py-4 table-heading">
                                Profesional
                            </th>

                            <th class="px-6 py-4 table-heading">
                                Documento
                            </th>

                            <th class="px-6 py-4 table-heading">
                                Colegiatura
                            </th>

                            <th class="px-6 py-4 table-heading">
                                Contacto
                            </th>

                            <th class="px-6 py-4 table-heading">
                                Estado
                            </th>

                            <th class="w-16 px-6 py-4"></th>

                        </tr>

                    </thead>


                    <tbody
                        class="
                            divide-y
                            divide-slate-100
                        "
                    >

                        <tr
                            v-for="profesional in profesionales.data"
                            :key="profesional.id"
                            class="
                                transition
                                hover:bg-slate-50
                            "
                        >

                            <td class="px-6 py-4">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-3
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
                                            text-sm
                                            font-bold
                                            text-clinica-700
                                        "
                                    >
                                        {{ iniciales(profesional) }}
                                    </div>


                                    <div>

                                        <p
                                            class="
                                                text-sm
                                                font-semibold
                                                text-slate-900
                                            "
                                        >
                                            {{ profesional.nombres }}
                                            {{ profesional.apellidos }}
                                        </p>

                                        <p
                                            class="
                                                mt-0.5
                                                text-xs
                                                text-slate-400
                                            "
                                        >
                                            Profesional odontológico
                                        </p>

                                    </div>

                                </div>

                            </td>


                            <td class="px-6 py-4">

                                <p
                                    class="
                                        text-sm
                                        text-slate-600
                                    "
                                >
                                    {{ profesional.numero_documento }}
                                </p>

                            </td>


                            <td class="px-6 py-4">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                    "
                                >

                                    <BadgeCheck
                                        :size="16"
                                        class="text-clinica-600"
                                    />

                                    <span
                                        class="
                                            text-sm
                                            font-medium
                                            text-slate-700
                                        "
                                    >
                                        {{ profesional.numero_colegiatura }}
                                    </span>

                                </div>

                            </td>


                            <td class="px-6 py-4">

                                <div
                                    v-if="profesional.telefono"
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                        text-sm
                                        text-slate-600
                                    "
                                >
                                    <Phone :size="14" />
                                    {{ profesional.telefono }}
                                </div>


                                <div
                                    v-if="profesional.correo"
                                    class="
                                        mt-1
                                        flex
                                        items-center
                                        gap-2
                                        text-xs
                                        text-slate-400
                                    "
                                >
                                    <Mail :size="13" />
                                    {{ profesional.correo }}
                                </div>

                            </td>


                            <td class="px-6 py-4">

                                <span
                                    class="
                                        inline-flex
                                        rounded-full
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-semibold
                                    "
                                    :class="
                                        profesional.activo
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                >
                                    {{
                                        profesional.activo
                                            ? 'Activo'
                                            : 'Inactivo'
                                    }}
                                </span>

                            </td>


                            <td class="px-6 py-4">

                                <div class="flex gap-1">

                                    <button
                                        type="button"
                                        title="Editar"
                                        class="
                                            flex
                                            h-9
                                            w-9
                                            items-center
                                            justify-center
                                            rounded-lg
                                            text-slate-400
                                            transition
                                            hover:bg-clinica-50
                                            hover:text-clinica-700
                                        "
                                        @click="
                                            editarProfesional(
                                                profesional
                                            )
                                        "
                                    >
                                        <Pencil :size="16" />
                                    </button>


                                    <button
                                        v-if="profesional.activo"
                                        type="button"
                                        class="
                                            rounded-lg
                                            px-2
                                            text-xs
                                            font-semibold
                                            text-rose-600
                                            hover:bg-rose-50
                                        "
                                        @click="
                                            desactivar(
                                                profesional
                                            )
                                        "
                                    >
                                        Desactivar
                                    </button>


                                    <button
                                        v-else
                                        type="button"
                                        class="
                                            rounded-lg
                                            px-2
                                            text-xs
                                            font-semibold
                                            text-emerald-700
                                            hover:bg-emerald-50
                                        "
                                        @click="
                                            reactivar(
                                                profesional
                                            )
                                        "
                                    >
                                        Reactivar
                                    </button>

                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- SIN RESULTADOS -->

            <div
                v-if="!hayProfesionales"
                class="
                    flex
                    min-h-72
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
                        h-16
                        w-16
                        items-center
                        justify-center
                        rounded-2xl
                        bg-slate-100
                        text-slate-400
                    "
                >
                    <Stethoscope :size="27" />
                </div>

                <h3
                    class="
                        mt-5
                        text-sm
                        font-semibold
                        text-slate-900
                    "
                >
                    No encontramos profesionales
                </h3>

                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    "
                >
                    Registra un profesional para comenzar.
                </p>

            </div>


            <!-- PAGINACIÓN -->

            <div
                v-if="
                    hayProfesionales &&
                    profesionales.links.length > 3
                "
                class="
                    flex
                    flex-wrap
                    items-center
                    justify-between
                    gap-4
                    border-t
                    border-slate-100
                    p-4
                "
            >

                <p class="text-xs text-slate-500">
                    Mostrando

                    {{ profesionales.from }}

                    -

                    {{ profesionales.to }}

                    de

                    {{ profesionales.total }}
                </p>


                <div class="flex flex-wrap gap-1">

                    <template
                        v-for="link in profesionales.links"
                        :key="link.label"
                    >

                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="
                                flex
                                min-h-9
                                min-w-9
                                items-center
                                justify-center
                                rounded-lg
                                border
                                px-3
                                text-xs
                                font-semibold
                            "
                            :class="
                                link.active
                                    ? 'border-clinica-700 bg-clinica-700 text-white'
                                    : 'border-slate-200 bg-white text-slate-600'
                            "
                        >
                            <span v-html="link.label"></span>
                        </Link>

                    </template>

                </div>

            </div>

        </section>


        <ProfesionalFormDrawer
            :open="drawerAbierto"
            :profesional="profesionalSeleccionado"
            @close="cerrarDrawer"
        />

    </AppLayout>

</template>