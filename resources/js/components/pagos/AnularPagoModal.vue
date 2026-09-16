<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    X,
    Ban,
    LoaderCircle,
} from 'lucide-vue-next';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },

    pago: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits([
    'close',
]);

const form = useForm({
    motivo_anulacion: '',
});

watch(
    () => props.open,
    abierto => {
        if (abierto) {
            form.reset();
            form.clearErrors();
        }
    }
);

function cerrar() {
    if (form.processing) {
        return;
    }

    emit('close');
}

function anular() {
    if (!props.pago?.id) {
        return;
    }

    form.patch(
        `/clinica/pagos/${props.pago.id}/anular`,
        {
            preserveScroll: true,

            onSuccess: () => {
                emit('close');
            },
        }
    );
}
</script>

<template>
    <div>
        <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-[2px]"
                @click.self="cerrar"
            >
                <div
                    class="w-full max-w-md rounded-3xl bg-white shadow-2xl"
                >
                    <header
                        class="flex items-center justify-between border-b border-slate-100 px-6 py-5"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600"
                            >
                                <Ban :size="19" />
                            </div>

                            <div>
                                <h2 class="font-bold text-slate-900">
                                    Anular pago
                                </h2>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Se conservará para auditoría.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-xl p-2 text-slate-400 hover:bg-slate-100"
                            @click="cerrar"
                        >
                            <X :size="18" />
                        </button>
                    </header>

                    <form
                        class="p-6"
                        @submit.prevent="anular"
                    >
                        <label class="form-label">
                            Motivo de anulación
                        </label>

                        <textarea
                            v-model="form.motivo_anulacion"
                            rows="4"
                            class="input-clinica py-3"
                            placeholder="Explica por qué se anula este pago..."
                        />

                        <p
                            v-if="form.errors.motivo_anulacion"
                            class="error-text"
                        >
                            {{ form.errors.motivo_anulacion }}
                        </p>

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                @click="cerrar"
                            >
                                Volver
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60"
                            >
                                <LoaderCircle
                                    v-if="form.processing"
                                    :size="16"
                                    class="animate-spin"
                                />

                                <Ban
                                    v-else
                                    :size="16"
                                />

                                Anular pago
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </div>
</template>
