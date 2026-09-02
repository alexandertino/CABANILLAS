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

import ConsultorioFormDrawer
    from '@/components/consultorios/ConsultorioFormDrawer.vue';

import {
    DoorOpen,
    Plus,
    Search,
    Pencil,
    SlidersHorizontal,
    X,
} from 'lucide-vue-next';


const props = defineProps({

    consultorios: {
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

const consultorioSeleccionado = ref(null);

let temporizador = null;


watch(buscar, () => {

    clearTimeout(temporizador);

    temporizador = setTimeout(() => {
        aplicarFiltros();
    }, 400);

});


watch(estado, aplicarFiltros);


function aplicarFiltros() {

    router.get(
        '/clinica/consultorios',
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


function nuevoConsultorio() {

    consultorioSeleccionado.value = null;

    drawerAbierto.value = true;
}


function editarConsultorio(consultorio) {

    consultorioSeleccionado.value =
        consultorio;

    drawerAbierto.value = true;
}


function cerrarDrawer() {

    drawerAbierto.value = false;

    consultorioSeleccionado.value = null;
}


function desactivar(consultorio) {

    if (
        !confirm(
            `¿Desactivar ${consultorio.nombre}?`
        )
    ) {
        return;
    }

    router.patch(
        `/clinica/consultorios/${consultorio.id}/desactivar`,
        {},
        {
            preserveScroll: true,
        }
    );
}


function reactivar(consultorio) {

    router.patch(
        `/clinica/consultorios/${consultorio.id}/reactivar`,
        {},
        {
            preserveScroll: true,
        }
    );
}


const hayConsultorios = computed(() =>
    props.consultorios.data.length > 0
);
</script>


<template>

    <Head title="Consultorios" />


    <AppLayout
        titulo="Consultorios"
        descripcion="Administra los ambientes de atención de la clínica"
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
                    <DoorOpen :size="21" />
                </div>


                <div>

                    <h2
                        class="
                            text-xl
                            font-bold
                            text-slate-900
                        "
                    >
                        Consultorios
                    </h2>

                    <p
                        class="
                            mt-0.5
                            text-sm
                            text-slate-500
                        "
                    >
                        {{ consultorios.total }}
                        registrados
                    </p>

                </div>

            </div>


            <button
                type="button"
                class="
                    inline-flex
                    items-center
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
                @click="nuevoConsultorio"
            >
                <Plus :size="18" />

                Nuevo consultorio
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
                    sm:flex-row
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
                        placeholder="Buscar consultorio..."
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
                        @click="buscar = ''"
                    >
                        <X :size="16" />
                    </button>

                </div>


                <div class="flex items-center gap-2">

                    <SlidersHorizontal
                        :size="18"
                        class="text-slate-400"
                    />

                    <select
                        v-model="estado"
                        class="input-clinica min-w-40"
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


        <!-- LISTADO -->

        <section
            class="
                mt-5
                grid
                gap-4
                sm:grid-cols-2
                xl:grid-cols-3
            "
        >

            <article
                v-for="consultorio in consultorios.data"
                :key="consultorio.id"
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    shadow-sm
                    transition
                    hover:-translate-y-0.5
                    hover:shadow-md
                "
            >

                <div
                    class="
                        flex
                        items-start
                        justify-between
                        gap-4
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
                        <DoorOpen :size="20" />
                    </div>


                    <span
                        class="
                            rounded-full
                            px-2.5
                            py-1
                            text-xs
                            font-semibold
                        "
                        :class="
                            consultorio.activo
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'bg-slate-100 text-slate-500'
                        "
                    >
                        {{
                            consultorio.activo
                                ? 'Activo'
                                : 'Inactivo'
                        }}
                    </span>

                </div>


                <p
                    class="
                        mt-5
                        text-xs
                        font-bold
                        uppercase
                        tracking-wide
                        text-clinica-700
                    "
                >
                    {{ consultorio.codigo }}
                </p>


                <h3
                    class="
                        mt-1
                        text-base
                        font-bold
                        text-slate-900
                    "
                >
                    {{ consultorio.nombre }}
                </h3>


                <p
                    class="
                        mt-2
                        min-h-10
                        text-sm
                        leading-5
                        text-slate-500
                    "
                >
                    {{
                        consultorio.descripcion ||
                        'Sin descripción registrada.'
                    }}
                </p>


                <div
                    class="
                        mt-5
                        flex
                        items-center
                        justify-between
                        border-t
                        border-slate-100
                        pt-4
                    "
                >

                    <button
                        type="button"
                        class="
                            inline-flex
                            items-center
                            gap-2
                            text-sm
                            font-semibold
                            text-clinica-700
                        "
                        @click="
                            editarConsultorio(
                                consultorio
                            )
                        "
                    >
                        <Pencil :size="15" />

                        Editar
                    </button>


                    <button
                        v-if="consultorio.activo"
                        type="button"
                        class="
                            text-xs
                            font-semibold
                            text-rose-600
                        "
                        @click="
                            desactivar(
                                consultorio
                            )
                        "
                    >
                        Desactivar
                    </button>


                    <button
                        v-else
                        type="button"
                        class="
                            text-xs
                            font-semibold
                            text-emerald-700
                        "
                        @click="
                            reactivar(
                                consultorio
                            )
                        "
                    >
                        Reactivar
                    </button>

                </div>

            </article>

        </section>


        <!-- EMPTY -->

        <section
            v-if="!hayConsultorios"
            class="
                mt-5
                flex
                min-h-72
                flex-col
                items-center
                justify-center
                rounded-2xl
                border
                border-slate-200
                bg-white
                text-center
            "
        >

            <DoorOpen
                :size="30"
                class="text-slate-300"
            />

            <h3
                class="
                    mt-4
                    font-semibold
                    text-slate-800
                "
            >
                No hay consultorios
            </h3>

            <p
                class="
                    mt-1
                    text-sm
                    text-slate-500
                "
            >
                Registra el primer ambiente de atención.
            </p>

        </section>


        <!-- PAGINACIÓN -->

        <div
            v-if="
                hayConsultorios &&
                consultorios.links.length > 3
            "
            class="
                mt-5
                flex
                flex-wrap
                gap-1
            "
        >

            <template
                v-for="link in consultorios.links"
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


        <ConsultorioFormDrawer
            :open="drawerAbierto"
            :consultorio="consultorioSeleccionado"
            @close="cerrarDrawer"
        />

    </AppLayout>

</template>