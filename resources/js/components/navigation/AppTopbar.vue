<script setup>
import {
    Menu,
    Search,
    Bell,
    ChevronDown,
} from 'lucide-vue-next';

defineProps({
    titulo: {
        type: String,
        default: 'Dashboard',
    },

    descripcion: {
        type: String,
        default: '',
    },
});

const emit = defineEmits([
    'menu',
]);
</script>

<template>

    <header
        class="
            sticky
            top-0
            z-30
            flex
            h-20
            items-center
            justify-between
            border-b
            border-slate-200/80
            bg-white/90
            px-5
            backdrop-blur-xl
            lg:px-8
        "
    >

        <div class="flex items-center gap-4">

            <button
                type="button"
                class="
                    flex
                    h-10
                    w-10
                    items-center
                    justify-center
                    rounded-xl
                    border
                    border-slate-200
                    text-slate-600
                    transition
                    hover:bg-slate-50
                    lg:hidden
                "
                @click="emit('menu')"
            >
                <Menu :size="20" />
            </button>

            <div>

                <h1
                    class="
                        text-lg
                        font-bold
                        tracking-tight
                        text-slate-900
                        sm:text-xl
                    "
                >
                    {{ titulo }}
                </h1>

                <p
                    v-if="descripcion"
                    class="
                        mt-0.5
                        hidden
                        text-xs
                        text-slate-500
                        sm:block
                    "
                >
                    {{ descripcion }}
                </p>

            </div>

        </div>


        <div class="flex items-center gap-2">

            <!-- BUSCADOR -->

            <div
                class="
                    hidden
                    h-10
                    w-64
                    items-center
                    gap-2
                    rounded-xl
                    border
                    border-slate-200
                    bg-slate-50
                    px-3
                    lg:flex
                "
            >
                <Search
                    :size="16"
                    class="text-slate-400"
                />

                <input
                    type="text"
                    placeholder="Buscar..."
                    class="
                        w-full
                        border-none
                        bg-transparent
                        text-sm
                        text-slate-700
                        outline-none
                        placeholder:text-slate-400
                    "
                >
            </div>


            <!-- NOTIFICACIONES -->

            <button
                type="button"
                class="
                    relative
                    flex
                    h-10
                    w-10
                    items-center
                    justify-center
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    text-slate-600
                    transition
                    hover:bg-slate-50
                "
            >
                <Bell :size="18" />

                <span
                    class="
                        absolute
                        right-2
                        top-2
                        h-2
                        w-2
                        rounded-full
                        bg-rose-500
                        ring-2
                        ring-white
                    "
                ></span>
            </button>


            <!-- USUARIO -->

            <button
                type="button"
                class="
                    ml-1
                    hidden
                    items-center
                    gap-2
                    rounded-xl
                    px-2
                    py-1.5
                    transition
                    hover:bg-slate-50
                    sm:flex
                "
            >

                <div
                    class="
                        flex
                        h-8
                        w-8
                        items-center
                        justify-center
                        rounded-lg
                        bg-clinica-100
                        text-xs
                        font-bold
                        text-clinica-800
                    "
                >
                    AD
                </div>

                <ChevronDown
                    :size="14"
                    class="text-slate-400"
                />

            </button>

        </div>

    </header>

</template>