<script setup>
import {
    CheckCircle2,
    AlertCircle,
    TriangleAlert,
    X,
} from 'lucide-vue-next';

defineProps({
    visible: {
        type: Boolean,
        default: false,
    },

    tipo: {
        type: String,
        default: 'success',
    },

    mensaje: {
        type: String,
        default: '',
    },
});

defineEmits([
    'close',
]);
</script>

<template>

    <Transition
        enter-active-class="transition duration-300 ease-out"
        leave-active-class="transition duration-200 ease-in"
        enter-from-class="translate-y-3 opacity-0 sm:translate-x-5 sm:translate-y-0"
        leave-to-class="translate-y-3 opacity-0 sm:translate-x-5 sm:translate-y-0"
    >

        <div
            v-if="visible && mensaje"
            class="
                fixed
                bottom-5
                right-5
                z-[200]
                w-[calc(100%-2.5rem)]
                max-w-sm
                rounded-2xl
                border
                bg-white
                p-4
                shadow-xl
            "
            :class="{
                'border-emerald-200':
                    tipo === 'success',

                'border-rose-200':
                    tipo === 'error',

                'border-amber-200':
                    tipo === 'warning',
            }"
        >

            <div class="flex items-start gap-3">

                <div
                    class="
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                    "
                    :class="{
                        'bg-emerald-50 text-emerald-600':
                            tipo === 'success',

                        'bg-rose-50 text-rose-600':
                            tipo === 'error',

                        'bg-amber-50 text-amber-600':
                            tipo === 'warning',
                    }"
                >

                    <CheckCircle2
                        v-if="tipo === 'success'"
                        :size="20"
                    />

                    <AlertCircle
                        v-else-if="tipo === 'error'"
                        :size="20"
                    />

                    <TriangleAlert
                        v-else
                        :size="20"
                    />

                </div>


                <div class="min-w-0 flex-1">

                    <p
                        class="
                            text-sm
                            font-bold
                            text-slate-900
                        "
                    >
                        {{
                            tipo === 'success'
                                ? 'Operación realizada'
                                : tipo === 'error'
                                    ? 'Ocurrió un problema'
                                    : 'Atención'
                        }}
                    </p>

                    <p
                        class="
                            mt-1
                            text-sm
                            leading-5
                            text-slate-500
                        "
                    >
                        {{ mensaje }}
                    </p>

                </div>


                <button
                    type="button"
                    class="
                        flex
                        h-8
                        w-8
                        shrink-0
                        items-center
                        justify-center
                        rounded-lg
                        text-slate-400
                        transition
                        hover:bg-slate-100
                        hover:text-slate-700
                    "
                    @click="$emit('close')"
                >
                    <X :size="16" />
                </button>

            </div>

        </div>

    </Transition>

</template>