<script setup>
import {
    UserRound,
    X,
    FileText,
    Phone,
    Mail,
    MapPin,
    CalendarDays,
    HeartHandshake,
    NotebookText,
} from 'lucide-vue-next';


defineProps({
    open: {
        type: Boolean,
        default: false,
    },

    paciente: {
        type: Object,
        default: null,
    },
});


defineEmits([
    'close',
    'edit',
]);


function mostrar(valor) {
    return valor || 'No registrado';
}


function fecha(fecha) {

    if (!fecha) {
        return 'No registrada';
    }

    return new Intl.DateTimeFormat(
        'es-PE',
        {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            timeZone: 'UTC',
        }
    ).format(
        new Date(fecha)
    );
}
</script>


<template>

    <div>

        <Transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="
                    fixed
                    inset-0
                    z-[90]
                    bg-slate-950/40
                    backdrop-blur-[2px]
                "
                @click="$emit('close')"
            ></div>
        </Transition>


        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            leave-active-class="transition-transform duration-200 ease-in"
            enter-from-class="translate-x-full"
            leave-to-class="translate-x-full"
        >

            <aside
                v-if="open && paciente"
                class="
                    fixed
                    inset-y-0
                    right-0
                    z-[100]
                    flex
                    w-full
                    max-w-xl
                    flex-col
                    border-l
                    border-slate-200
                    bg-white
                    shadow-2xl
                "
            >

                <!-- HEADER -->

                <header
                    class="
                        flex
                        items-center
                        justify-between
                        border-b
                        border-slate-200
                        px-6
                        py-5
                    "
                >

                    <div>

                        <p
                            class="
                                text-xs
                                font-semibold
                                text-clinica-700
                            "
                        >
                            {{ paciente.codigo }}
                        </p>

                        <h2
                            class="
                                mt-1
                                text-lg
                                font-bold
                                text-slate-900
                            "
                        >
                            {{ paciente.nombres }}
                            {{ paciente.apellidos }}
                        </h2>

                    </div>


                    <button
                        type="button"
                        class="
                            flex
                            h-10
                            w-10
                            items-center
                            justify-center
                            rounded-xl
                            text-slate-400
                            hover:bg-slate-100
                        "
                        @click="$emit('close')"
                    >
                        <X :size="20" />
                    </button>

                </header>


                <div
                    class="
                        flex-1
                        overflow-y-auto
                        p-6
                    "
                >

                    <!-- IDENTIDAD -->

                    <div
                        class="
                            flex
                            items-center
                            gap-4
                            rounded-2xl
                            bg-slate-50
                            p-5
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
                                bg-clinica-100
                                text-clinica-700
                            "
                        >
                            <UserRound :size="26" />
                        </div>

                        <div>

                            <p
                                class="
                                    font-bold
                                    text-slate-900
                                "
                            >
                                {{ paciente.nombres }}
                                {{ paciente.apellidos }}
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-sm
                                    text-slate-500
                                "
                            >
                                {{ paciente.tipo_documento }}
                                {{ paciente.numero_documento }}
                            </p>

                            <span
                                class="
                                    mt-2
                                    inline-flex
                                    rounded-full
                                    px-2.5
                                    py-1
                                    text-xs
                                    font-semibold
                                "
                                :class="
                                    paciente.activo
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-slate-200 text-slate-600'
                                "
                            >
                                {{
                                    paciente.activo
                                        ? 'Activo'
                                        : 'Inactivo'
                                }}
                            </span>

                        </div>

                    </div>


                    <!-- DATOS -->

                    <section class="mt-7">

                        <h3
                            class="
                                text-sm
                                font-bold
                                text-slate-900
                            "
                        >
                            Datos personales
                        </h3>


                        <div
                            class="
                                mt-4
                                grid
                                gap-4
                                sm:grid-cols-2
                            "
                        >

                            <div class="detail-card">
                                <CalendarDays :size="17" />

                                <div>
                                    <p class="detail-label">
                                        Nacimiento
                                    </p>

                                    <p class="detail-value">
                                        {{
                                            fecha(
                                                paciente.fecha_nacimiento
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>


                            <div class="detail-card">
                                <FileText :size="17" />

                                <div>
                                    <p class="detail-label">
                                        Documento
                                    </p>

                                    <p class="detail-value">
                                        {{
                                            paciente.tipo_documento
                                        }}
                                        {{
                                            paciente.numero_documento
                                        }}
                                    </p>
                                </div>
                            </div>


                            <div class="detail-card">
                                <Phone :size="17" />

                                <div>
                                    <p class="detail-label">
                                        Teléfono
                                    </p>

                                    <p class="detail-value">
                                        {{
                                            mostrar(
                                                paciente.telefono
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>


                            <div class="detail-card">
                                <Mail :size="17" />

                                <div>
                                    <p class="detail-label">
                                        Correo
                                    </p>

                                    <p class="detail-value">
                                        {{
                                            mostrar(
                                                paciente.correo
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                        </div>


                        <div
                            class="
                                detail-card
                                mt-4
                            "
                        >
                            <MapPin :size="17" />

                            <div>
                                <p class="detail-label">
                                    Dirección
                                </p>

                                <p class="detail-value">
                                    {{
                                        mostrar(
                                            paciente.direccion
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                    </section>


                    <!-- EMERGENCIA -->

                    <section
                        class="
                            mt-8
                            border-t
                            border-slate-100
                            pt-7
                        "
                    >

                        <div
                            class="
                                flex
                                items-center
                                gap-2
                            "
                        >
                            <HeartHandshake
                                :size="18"
                                class="text-rose-500"
                            />

                            <h3
                                class="
                                    text-sm
                                    font-bold
                                    text-slate-900
                                "
                            >
                                Contacto de emergencia
                            </h3>
                        </div>


                        <div
                            class="
                                mt-4
                                rounded-2xl
                                border
                                border-slate-200
                                p-4
                            "
                        >

                            <p
                                class="
                                    text-sm
                                    font-semibold
                                    text-slate-800
                                "
                            >
                                {{
                                    mostrar(
                                        paciente.nombre_contacto_emergencia
                                    )
                                }}
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-sm
                                    text-slate-500
                                "
                            >
                                {{
                                    mostrar(
                                        paciente.telefono_contacto_emergencia
                                    )
                                }}
                            </p>

                        </div>

                    </section>


                    <!-- OBSERVACIONES -->

                    <section
                        class="
                            mt-8
                            border-t
                            border-slate-100
                            pt-7
                        "
                    >

                        <div
                            class="
                                flex
                                items-center
                                gap-2
                            "
                        >
                            <NotebookText
                                :size="18"
                                class="text-slate-500"
                            />

                            <h3
                                class="
                                    text-sm
                                    font-bold
                                    text-slate-900
                                "
                            >
                                Observaciones
                            </h3>
                        </div>

                        <p
                            class="
                                mt-4
                                rounded-2xl
                                bg-slate-50
                                p-4
                                text-sm
                                leading-6
                                text-slate-600
                            "
                        >
                            {{
                                mostrar(
                                    paciente.observaciones
                                )
                            }}
                        </p>

                    </section>

                </div>


                <footer
                    class="
                        flex
                        justify-end
                        gap-3
                        border-t
                        border-slate-200
                        p-4
                    "
                >

                    <button
                        type="button"
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            px-4
                            py-2.5
                            text-sm
                            font-semibold
                            text-slate-700
                        "
                        @click="$emit('close')"
                    >
                        Cerrar
                    </button>


                    <button
                        type="button"
                        class="
                            rounded-xl
                            bg-clinica-700
                            px-4
                            py-2.5
                            text-sm
                            font-semibold
                            text-white
                        "
                        @click="$emit('edit', paciente)"
                    >
                        Editar paciente
                    </button>

                </footer>

            </aside>

        </Transition>

    </div>

</template>