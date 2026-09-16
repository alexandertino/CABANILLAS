<script setup>
import {
    Head,
    useForm,
} from '@inertiajs/vue3';

import {
    LoaderCircle,
    LockKeyhole,
    Mail,
    ShieldCheck,
    Stethoscope,
} from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function iniciarSesion() {
    if (form.processing) {
        return;
    }

    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Iniciar sesión" />

    <main
        class="relative min-h-screen overflow-hidden bg-slate-50"
    >
        <div
            class="absolute -right-28 -top-28 h-96 w-96 rounded-full bg-clinica-100/70 blur-3xl"
        ></div>

        <div
            class="absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-cyan-100/60 blur-3xl"
        ></div>

        <div
            class="relative mx-auto grid min-h-screen max-w-7xl items-stretch lg:grid-cols-[1.05fr_0.95fr] lg:p-6"
        >
            <section
                class="relative hidden overflow-hidden rounded-[2rem] bg-gradient-to-br from-clinica-700 via-clinica-800 to-clinica-950 p-12 text-white shadow-xl lg:flex lg:flex-col lg:justify-between"
            >
                <div
                    class="absolute -right-24 -top-24 h-80 w-80 rounded-full border border-white/10 bg-white/5"
                ></div>

                <div
                    class="absolute -bottom-32 right-10 h-96 w-96 rounded-full border border-cyan-300/10 bg-cyan-300/5"
                ></div>

                <div class="relative flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 backdrop-blur"
                    >
                        <Stethoscope :size="25" />
                    </div>

                    <div>
                        <p class="font-bold tracking-tight">
                            Clínica Cabanillas
                        </p>

                        <p class="mt-0.5 text-xs text-teal-100/75">
                            Sistema de gestión odontológica
                        </p>
                    </div>
                </div>

                <div class="relative max-w-xl">
                    <div
                        class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold backdrop-blur"
                    >
                        <ShieldCheck :size="15" />
                        Acceso seguro al sistema
                    </div>

                    <h1
                        class="text-4xl font-bold leading-tight tracking-tight xl:text-5xl"
                    >
                        Tu clínica, organizada en un solo lugar.
                    </h1>

                    <p
                        class="mt-5 max-w-lg text-sm leading-7 text-teal-50/75"
                    >
                        Ingresa para gestionar pacientes, citas,
                        tratamientos y pagos de forma centralizada.
                    </p>
                </div>

                <p class="relative text-xs text-teal-100/60">
                    Clínica Odontológica Cabanillas
                </p>
            </section>

            <section
                class="flex items-center justify-center px-5 py-10 sm:px-10 lg:px-16"
            >
                <div class="w-full max-w-md">
                    <div class="mb-9 lg:hidden">
                        <img
                            src="/images/logo-clinica.svg"
                            alt="Clínica Cabanillas"
                            class="h-16 w-auto"
                        >
                    </div>

                    <div class="mb-8">
                        <p
                            class="text-sm font-semibold text-clinica-700"
                        >
                            Bienvenido
                        </p>

                        <h2
                            class="mt-2 text-3xl font-bold tracking-tight text-slate-900"
                        >
                            Iniciar sesión
                        </h2>

                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Usa tus credenciales para acceder al sistema.
                        </p>
                    </div>

                    <form
                        class="space-y-5"
                        @submit.prevent="iniciarSesion"
                    >
                        <div>
                            <label
                                for="email"
                                class="form-label"
                            >
                                Correo electrónico
                            </label>

                            <div class="relative">
                                <Mail
                                    :size="18"
                                    class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
                                />

                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    name="email"
                                    autocomplete="username"
                                    autofocus
                                    required
                                    class="input-clinica pl-11"
                                    :class="{
                                        'input-error': form.errors.email,
                                    }"
                                    placeholder="nombre@correo.com"
                                >
                            </div>

                            <p
                                v-if="form.errors.email"
                                class="error-text"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="password"
                                class="form-label"
                            >
                                Contraseña
                            </label>

                            <div class="relative">
                                <LockKeyhole
                                    :size="18"
                                    class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
                                />

                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                    class="input-clinica pl-11"
                                    :class="{
                                        'input-error': form.errors.password,
                                    }"
                                    placeholder="Ingresa tu contraseña"
                                >
                            </div>

                            <p
                                v-if="form.errors.password"
                                class="error-text"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <label
                            class="inline-flex cursor-pointer items-center gap-2.5 text-sm text-slate-600"
                        >
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                name="remember"
                                class="h-4 w-4 rounded border-slate-300 text-clinica-700 accent-clinica-700 focus:ring-clinica-500"
                            >

                            Recordarme
                        </label>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-clinica-700 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-clinica-800 focus:outline-none focus:ring-4 focus:ring-clinica-100 disabled:cursor-not-allowed disabled:opacity-65"
                        >
                            <LoaderCircle
                                v-if="form.processing"
                                :size="18"
                                class="animate-spin"
                            />

                            {{
                                form.processing
                                    ? 'Ingresando...'
                                    : 'Iniciar sesión'
                            }}
                        </button>
                    </form>

                    <p
                        class="mt-8 text-center text-xs leading-5 text-slate-400"
                    >
                        Acceso exclusivo para personal autorizado.
                    </p>
                </div>
            </section>
        </div>
    </main>
</template>
