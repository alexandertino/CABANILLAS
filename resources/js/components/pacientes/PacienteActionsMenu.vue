<script setup>
import {
    ref,
    onMounted,
    onBeforeUnmount,
} from 'vue';

import {
    MoreHorizontal,
    Eye,
    Pencil,
    UserX,
    UserCheck,
} from 'lucide-vue-next';


defineProps({
    paciente: {
        type: Object,
        required: true,
    },
});


const emit = defineEmits([
    'view',
    'edit',
    'deactivate',
    'reactivate',
]);


const abierto = ref(false);

const contenedor = ref(null);


function toggle() {
    abierto.value = !abierto.value;
}


function cerrar() {
    abierto.value = false;
}


function seleccionar(accion) {

    cerrar();

    emit(accion);
}


function clickExterior(event) {

    if (
        contenedor.value &&
        !contenedor.value.contains(event.target)
    ) {
        cerrar();
    }
}


onMounted(() => {
    document.addEventListener(
        'click',
        clickExterior
    );
});


onBeforeUnmount(() => {
    document.removeEventListener(
        'click',
        clickExterior
    );
});
</script>


<template>

    <div
        ref="contenedor"
        class="relative"
    >

        <button
            type="button"
            class="
                flex
                h-9
                w-9
                items-center
                justify-center
                rounded-lg
                text-slate-400
                transition
                hover:bg-slate-100
                hover:text-slate-700
            "
            @click.stop="toggle"
        >
            <MoreHorizontal :size="18" />
        </button>


        <Transition
            enter-active-class="transition duration-150"
            leave-active-class="transition duration-100"
            enter-from-class="scale-95 opacity-0"
            leave-to-class="scale-95 opacity-0"
        >

            <div
                v-if="abierto"
                class="
                    absolute
                    right-0
                    top-11
                    z-50
                    w-52
                    origin-top-right
                    overflow-hidden
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    p-1.5
                    shadow-lg
                "
            >

                <button
                    type="button"
                    class="menu-action"
                    @click="
                        seleccionar('view')
                    "
                >
                    <Eye :size="16" />

                    Ver información
                </button>


                <button
                    type="button"
                    class="menu-action"
                    @click="
                        seleccionar('edit')
                    "
                >
                    <Pencil :size="16" />

                    Editar
                </button>


                <div
                    class="
                        my-1
                        border-t
                        border-slate-100
                    "
                ></div>


                <button
                    v-if="paciente.activo"
                    type="button"
                    class="
                        menu-action
                        text-rose-600
                        hover:bg-rose-50
                    "
                    @click="
                        seleccionar('deactivate')
                    "
                >
                    <UserX :size="16" />

                    Desactivar
                </button>


                <button
                    v-else
                    type="button"
                    class="
                        menu-action
                        text-emerald-700
                        hover:bg-emerald-50
                    "
                    @click="
                        seleccionar('reactivate')
                    "
                >
                    <UserCheck :size="16" />

                    Reactivar
                </button>

            </div>

        </Transition>

    </div>

</template>