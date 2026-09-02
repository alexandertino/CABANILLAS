<script setup>
import {
    ref,
    watch,
} from 'vue';

import { usePage } from '@inertiajs/vue3';

import AppToast from '@/components/ui/AppToast.vue';

const page = usePage();

const toastVisible = ref(false);
const toastMensaje = ref('');
const toastTipo = ref('success');

let toastTimeout = null;

function mostrarToast(tipo, mensaje) {

    if (!mensaje) {
        return;
    }

    toastTipo.value = tipo;
    toastMensaje.value = mensaje;
    toastVisible.value = true;

    clearTimeout(toastTimeout);

    toastTimeout = setTimeout(() => {
        toastVisible.value = false;
    }, 4000);
}

watch(
    () => page.props.flash,
    (flash) => {

        if (flash?.success) {
            mostrarToast(
                'success',
                flash.success
            );

            return;
        }

        if (flash?.error) {
            mostrarToast(
                'error',
                flash.error
            );

            return;
        }

        if (flash?.warning) {
            mostrarToast(
                'warning',
                flash.warning
            );
        }

    },
    {
        deep: true,
        immediate: true,
    }
);

import AppSidebar from '@/components/navigation/AppSidebar.vue';
import AppTopbar from '@/components/navigation/AppTopbar.vue';

defineProps({
    titulo: {
        type: String,
        default: 'Clínica Cabanillas',
    },

    descripcion: {
        type: String,
        default: '',
    },
});

const sidebarAbierto = ref(false);

function abrirSidebar() {
    sidebarAbierto.value = true;
}

function cerrarSidebar() {
    sidebarAbierto.value = false;
}
</script>

<template>

    <div
        class="
            min-h-screen
            bg-slate-50
        "
    >

        <AppSidebar
            :open="sidebarAbierto"
            @close="cerrarSidebar"
        />


        <!-- OVERLAY MOBILE -->

        <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="sidebarAbierto"
                class="
                    fixed
                    inset-0
                    z-40
                    bg-slate-950/40
                    backdrop-blur-sm
                    lg:hidden
                "
                @click="cerrarSidebar"
            ></div>
        </Transition>


        <!-- CONTENIDO -->

        <div class="lg:pl-72">

            <AppTopbar
                :titulo="titulo"
                :descripcion="descripcion"
                @menu="abrirSidebar"
            />

            <main
                class="
                    mx-auto
                    max-w-[1600px]
                    p-5
                    lg:p-8
                "
            >
                <slot />
            </main>

        </div>

    </div>
    
    <AppToast
        :visible="toastVisible"
        :tipo="toastTipo"
        :mensaje="toastMensaje"
        @close="toastVisible = false"
    />

</template>