<script setup>
import PacienteFormDrawer from '@/components/pacientes/PacienteFormDrawer.vue';

import PacienteActionsMenu
    from '@/components/pacientes/PacienteActionsMenu.vue';

import PacienteDetailDrawer
    from '@/components/pacientes/PacienteDetailDrawer.vue';


const detalleAbierto = ref(false);

const pacienteDetalle = ref(null);

function desactivarPaciente(paciente) {

    if (
        !confirm(
            `¿Desactivar a ${nombreCompleto(paciente)}?`
        )
    ) {
        return;
    }

    router.patch(
        `/clinica/pacientes/${paciente.id}/desactivar`,
        {},
        {
            preserveScroll: true,
        }
    );
}


function reactivarPaciente(paciente) {

    router.patch(
        `/clinica/pacientes/${paciente.id}/reactivar`,
        {},
        {
            preserveScroll: true,
        }
    );
}

function verPaciente(paciente) {

    pacienteDetalle.value = paciente;

    detalleAbierto.value = true;
}


function cerrarDetalle() {

    detalleAbierto.value = false;

    pacienteDetalle.value = null;
}
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
    Search,
    Plus,
    Users,
    MoreHorizontal,
    Pencil,
    Phone,
    Mail,
    FileText,
    UserRound,
    SlidersHorizontal,
    X,
} from 'lucide-vue-next';


const props = defineProps({
    pacientes: {
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
        '/clinica/pacientes',
        {
            buscar: buscar.value || undefined,

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


const hayPacientes = computed(() =>
    props.pacientes.data.length > 0
);


function nombreCompleto(paciente) {
    return `${paciente.nombres} ${paciente.apellidos}`;
}


function iniciales(paciente) {

    const nombre =
        paciente.nombres
            ?.trim()
            .charAt(0) ?? '';

    const apellido =
        paciente.apellidos
            ?.trim()
            .charAt(0) ?? '';

    return `${nombre}${apellido}`.toUpperCase();
}

const drawerPacienteAbierto = ref(false);

const pacienteSeleccionado = ref(null);

function abrirNuevoPaciente() {

    pacienteSeleccionado.value = null;

    drawerPacienteAbierto.value = true;
}


function editarPaciente(paciente) {

    pacienteSeleccionado.value = paciente;

    drawerPacienteAbierto.value = true;
}


function cerrarDrawerPaciente() {

    drawerPacienteAbierto.value = false;

    pacienteSeleccionado.value = null;
}

</script>


<template>

    <Head title="Pacientes" />


    <AppLayout
        titulo="Pacientes"
        descripcion="Gestiona los pacientes registrados en la clínica"
    >

        <!-- CABECERA DEL MÓDULO -->

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

            <div>

                <div
                    class="
                        flex
                        items-center
                        gap-2
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
                        <Users :size="20" />
                    </div>

                    <div>

                        <h2
                            class="
                                text-xl
                                font-bold
                                tracking-tight
                                text-slate-900
                            "
                        >
                            Directorio de pacientes
                        </h2>

                        <p
                            class="
                                mt-0.5
                                text-sm
                                text-slate-500
                            "
                        >
                            {{ pacientes.total }}
                            pacientes encontrados
                        </p>

                    </div>

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
                    shadow-sm
                    transition
                    hover:bg-clinica-800
                    focus:outline-none
                    focus:ring-4
                    focus:ring-clinica-100
                "
                @click="abrirNuevoPaciente"

            >
                <Plus :size="18" />

                Nuevo paciente
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
                    lg:items-center
                "
            >

                <!-- BUSCADOR -->

                <div
                    class="
                        relative
                        flex-1
                    "
                >

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
                        placeholder="Buscar por nombre, DNI, código o teléfono..."
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
                            text-slate-800
                            outline-none
                            transition
                            placeholder:text-slate-400
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
                            flex
                            -translate-y-1/2
                            items-center
                            justify-center
                            rounded-lg
                            p-1
                            text-slate-400
                            transition
                            hover:bg-slate-200
                            hover:text-slate-700
                        "
                        @click="limpiarBusqueda"
                    >
                        <X :size="16" />
                    </button>

                </div>


                <!-- FILTRO ESTADO -->

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
                            focus:border-clinica-400
                            focus:ring-4
                            focus:ring-clinica-50
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

            <!-- DESKTOP -->

            <div
                class="
                    hidden
                    overflow-x-auto
                    md:block
                "
            >

                <table
                    class="
                        w-full
                        border-collapse
                        text-left
                    "
                >

                    <thead
                        class="
                            border-b
                            border-slate-200
                            bg-slate-50/80
                        "
                    >

                        <tr>

                            <th
                                class="
                                    px-6
                                    py-4
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                "
                            >
                                Paciente
                            </th>

                            <th
                                class="
                                    px-6
                                    py-4
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                "
                            >
                                Documento
                            </th>

                            <th
                                class="
                                    px-6
                                    py-4
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                "
                            >
                                Contacto
                            </th>

                            <th
                                class="
                                    px-6
                                    py-4
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                "
                            >
                                Estado
                            </th>

                            <th
                                class="
                                    w-16
                                    px-6
                                    py-4
                                "
                            ></th>

                        </tr>

                    </thead>


                    <tbody
                        class="
                            divide-y
                            divide-slate-100
                        "
                    >

                        <tr
                            v-for="paciente in pacientes.data"
                            :key="paciente.id"
                            class="
                                transition
                                hover:bg-slate-50/70
                            "
                        >

                            <!-- PACIENTE -->

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
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-xl
                                            bg-clinica-50
                                            text-sm
                                            font-bold
                                            text-clinica-700
                                        "
                                    >
                                        {{ iniciales(paciente) }}
                                    </div>

                                    <div class="min-w-0">

                                        <p
                                            class="
                                                truncate
                                                text-sm
                                                font-semibold
                                                text-slate-900
                                            "
                                        >
                                            {{ nombreCompleto(paciente) }}
                                        </p>

                                        <p
                                            class="
                                                mt-0.5
                                                text-xs
                                                text-slate-400
                                            "
                                        >
                                            {{ paciente.codigo }}
                                        </p>

                                    </div>

                                </div>

                            </td>


                            <!-- DOCUMENTO -->

                            <td class="px-6 py-4">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                        text-sm
                                        text-slate-600
                                    "
                                >
                                    <FileText
                                        :size="15"
                                        class="text-slate-400"
                                    />

                                    <span>
                                        {{ paciente.numero_documento }}
                                    </span>
                                </div>

                                <p
                                    class="
                                        mt-1
                                        text-xs
                                        text-slate-400
                                    "
                                >
                                    {{ paciente.tipo_documento }}
                                </p>

                            </td>


                            <!-- CONTACTO -->

                            <td class="px-6 py-4">

                                <div
                                    v-if="paciente.telefono"
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                        text-sm
                                        text-slate-600
                                    "
                                >
                                    <Phone
                                        :size="14"
                                        class="text-slate-400"
                                    />

                                    {{ paciente.telefono }}
                                </div>

                                <div
                                    v-if="paciente.correo"
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

                                    {{ paciente.correo }}
                                </div>

                                <span
                                    v-if="
                                        !paciente.telefono &&
                                        !paciente.correo
                                    "
                                    class="
                                        text-sm
                                        text-slate-400
                                    "
                                >
                                    Sin contacto
                                </span>

                            </td>


                            <!-- ESTADO -->

                            <td class="px-6 py-4">

                                <span
                                    v-if="paciente.activo"
                                    class="
                                        inline-flex
                                        items-center
                                        gap-1.5
                                        rounded-full
                                        bg-emerald-50
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-semibold
                                        text-emerald-700
                                    "
                                >

                                    <span
                                        class="
                                            h-1.5
                                            w-1.5
                                            rounded-full
                                            bg-emerald-500
                                        "
                                    ></span>

                                    Activo

                                </span>

                                <span
                                    v-else
                                    class="
                                        inline-flex
                                        items-center
                                        gap-1.5
                                        rounded-full
                                        bg-slate-100
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-semibold
                                        text-slate-500
                                    "
                                >

                                    <span
                                        class="
                                            h-1.5
                                            w-1.5
                                            rounded-full
                                            bg-slate-400
                                        "
                                    ></span>

                                    Inactivo

                                </span>

                            </td>


                            <!-- OPCIONES -->

                            <td class="px-6 py-4">

                                <PacienteActionsMenu
    :paciente="paciente"
    @view="verPaciente(paciente)"
    @edit="editarPaciente(paciente)"
    @deactivate="
        desactivarPaciente(paciente)
    "
    @reactivate="
        reactivarPaciente(paciente)
    "
/>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- MOBILE -->

            <div
                class="
                    divide-y
                    divide-slate-100
                    md:hidden
                "
            >

                <article
                    v-for="paciente in pacientes.data"
                    :key="paciente.id"
                    class="p-5"
                >

                    <div
                        class="
                            flex
                            items-start
                            justify-between
                            gap-3
                        "
                    >

                        <div
                            class="
                                flex
                                min-w-0
                                items-center
                                gap-3
                            "
                        >

                            <div
                                class="
                                    flex
                                    h-11
                                    w-11
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-xl
                                    bg-clinica-50
                                    text-sm
                                    font-bold
                                    text-clinica-700
                                "
                            >
                                {{ iniciales(paciente) }}
                            </div>

                            <div class="min-w-0">

                                <h3
                                    class="
                                        truncate
                                        text-sm
                                        font-semibold
                                        text-slate-900
                                    "
                                >
                                    {{ nombreCompleto(paciente) }}
                                </h3>

                                <p
                                    class="
                                        mt-0.5
                                        text-xs
                                        text-slate-400
                                    "
                                >
                                    {{ paciente.numero_documento }}
                                </p>

                            </div>

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
                                paciente.activo
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-slate-100 text-slate-500'
                            "
                        >
                            {{
                                paciente.activo
                                    ? 'Activo'
                                    : 'Inactivo'
                            }}
                        </span>

                    </div>


                    <div
                        class="
                            mt-4
                            grid
                            gap-2
                            text-xs
                            text-slate-500
                        "
                    >

                        <div
                            v-if="paciente.telefono"
                            class="
                                flex
                                items-center
                                gap-2
                            "
                        >
                            <Phone :size="14" />
                            {{ paciente.telefono }}
                        </div>

                        <div
                            v-if="paciente.correo"
                            class="
                                flex
                                items-center
                                gap-2
                            "
                        >
                            <Mail :size="14" />
                            {{ paciente.correo }}
                        </div>

                    </div>

                </article>

            </div>


            <!-- EMPTY STATE -->

            <div
                v-if="!hayPacientes"
                class="
                    flex
                    min-h-80
                    flex-col
                    items-center
                    justify-center
                    px-6
                    py-12
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
                    <UserRound :size="28" />
                </div>

                <h3
                    class="
                        mt-5
                        text-sm
                        font-semibold
                        text-slate-900
                    "
                >
                    No encontramos pacientes
                </h3>

                <p
                    class="
                        mt-2
                        max-w-sm
                        text-sm
                        leading-6
                        text-slate-500
                    "
                >
                    Cambia los filtros o registra un nuevo
                    paciente para comenzar.
                </p>

            </div>


            <!-- PAGINACIÓN -->

            <div
                v-if="
                    hayPacientes &&
                    pacientes.links.length > 3
                "
                class="
                    flex
                    flex-col
                    gap-4
                    border-t
                    border-slate-100
                    px-5
                    py-4
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                "
            >

                <p
                    class="
                        text-xs
                        text-slate-500
                    "
                >
                    Mostrando

                    <span class="font-semibold">
                        {{ pacientes.from }}
                    </span>

                    -

                    <span class="font-semibold">
                        {{ pacientes.to }}
                    </span>

                    de

                    <span class="font-semibold">
                        {{ pacientes.total }}
                    </span>
                </p>


                <div
                    class="
                        flex
                        flex-wrap
                        gap-1
                    "
                >

                    <template
                        v-for="link in pacientes.links"
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
                                transition
                            "
                            :class="
                                link.active
                                    ? 'border-clinica-700 bg-clinica-700 text-white'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                            "
                        >
                            <span v-html="link.label"></span>
                        </Link>

                        <span
                            v-else
                            class="
                                flex
                                min-h-9
                                min-w-9
                                cursor-not-allowed
                                items-center
                                justify-center
                                rounded-lg
                                border
                                border-slate-100
                                bg-slate-50
                                px-3
                                text-xs
                                text-slate-300
                            "
                            v-html="link.label"
                        ></span>

                    </template>

                </div>

            </div>

        </section>
        
        
        <PacienteFormDrawer
            :open="drawerPacienteAbierto"
            :paciente="pacienteSeleccionado"
            @close="cerrarDrawerPaciente"
        />
        
        <PacienteDetailDrawer
            :open="detalleAbierto"
            :paciente="pacienteDetalle"
            @close="cerrarDetalle"
            @edit="
                (paciente) => {
                    cerrarDetalle();
                    editarPaciente(paciente);
                }
            "
        />

    </AppLayout>

</template>