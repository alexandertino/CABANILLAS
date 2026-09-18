<script setup>
import axios from 'axios'
import {
    computed,
    onMounted,
    reactive,
    ref,
} from 'vue'

const props = defineProps({
    paciente: {
        type: Object,
        required: true,
    },
})

/*
|--------------------------------------------------------------------------
| Estado general
|--------------------------------------------------------------------------
*/

const cargando = ref(true)
const guardando = ref(false)

const seguimientos = ref([])
const paginacion = ref(null)

const tratamientos = ref([])
const citas = ref([])

const errorGeneral = ref('')
const errores = ref({})

const editandoId = ref(null)

/*
|--------------------------------------------------------------------------
| Fotografías
|--------------------------------------------------------------------------
*/

const archivosPorSeguimiento = reactive({})

const subiendoSeguimientoId = ref(null)
const eliminandoImagenId = ref(null)

const imagenAbierta = ref(null)

/*
|--------------------------------------------------------------------------
| Formulario
|--------------------------------------------------------------------------
*/

const formulario = reactive({
    fecha_seguimiento: '',
    titulo: '',
    observaciones: '',
    tratamiento_paciente_id: '',
    cita_id: '',
})

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const nombrePaciente = computed(() => {
    return `${props.paciente.nombres} ${props.paciente.apellidos}`
})

const esEdicion = computed(() => {
    return editandoId.value !== null
})

const urlDatos = computed(() => {
    return `/clinica/pacientes/${props.paciente.id}/seguimientos`
})

/*
|--------------------------------------------------------------------------
| Fechas
|--------------------------------------------------------------------------
*/

function fechaLocalParaInput(fecha = new Date()) {
    const pad = (valor) =>
        String(valor).padStart(2, '0')

    return [
        fecha.getFullYear(),
        pad(fecha.getMonth() + 1),
        pad(fecha.getDate()),
    ].join('-')
        + 'T'
        + [
            pad(fecha.getHours()),
            pad(fecha.getMinutes()),
        ].join(':')
}

function convertirFechaParaInput(valor) {
    if (!valor) {
        return ''
    }

    const fecha = new Date(valor)

    if (
        Number.isNaN(
            fecha.getTime()
        )
    ) {
        return ''
    }

    return fechaLocalParaInput(fecha)
}

function mostrarFecha(valor) {
    if (!valor) {
        return '—'
    }

    const fecha = new Date(valor)

    if (
        Number.isNaN(
            fecha.getTime()
        )
    ) {
        return valor
    }

    return new Intl.DateTimeFormat(
        'es-PE',
        {
            dateStyle: 'medium',
            timeStyle: 'short',
        }
    ).format(fecha)
}

/*
|--------------------------------------------------------------------------
| Formulario seguimiento
|--------------------------------------------------------------------------
*/

function resetFormulario() {
    editandoId.value = null

    formulario.fecha_seguimiento =
        fechaLocalParaInput()

    formulario.titulo = ''
    formulario.observaciones = ''
    formulario.tratamiento_paciente_id = ''
    formulario.cita_id = ''

    errores.value = {}
    errorGeneral.value = ''
}

function editar(seguimiento) {
    editandoId.value =
        seguimiento.id

    formulario.fecha_seguimiento =
        convertirFechaParaInput(
            seguimiento.fecha_seguimiento
        )

    formulario.titulo =
        seguimiento.titulo ?? ''

    formulario.observaciones =
        seguimiento.observaciones ?? ''

    formulario.tratamiento_paciente_id =
        seguimiento.tratamiento_paciente_id
        ?? ''

    formulario.cita_id =
        seguimiento.cita_id
        ?? ''

    errores.value = {}
    errorGeneral.value = ''

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}

/*
|--------------------------------------------------------------------------
| Cargar datos
|--------------------------------------------------------------------------
*/

async function cargarSeguimientos(page = 1) {
    cargando.value = true
    errorGeneral.value = ''

    try {
        const respuesta = await axios.get(
            urlDatos.value,
            {
                params: {
                    page,
                },
            }
        )

        seguimientos.value =
            respuesta.data
                .seguimientos
                ?.data ?? []

        paginacion.value =
            respuesta.data
                .seguimientos ?? null

        tratamientos.value =
            respuesta.data
                .opciones
                ?.tratamientos ?? []

        citas.value =
            respuesta.data
                .opciones
                ?.citas ?? []
    } catch (error) {
        errorGeneral.value =
            error.response?.data?.message
            ?? 'No se pudo cargar el seguimiento clínico.'
    } finally {
        cargando.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Guardar seguimiento
|--------------------------------------------------------------------------
*/

async function guardar() {
    guardando.value = true

    errores.value = {}
    errorGeneral.value = ''

    const payload = {
        fecha_seguimiento:
            formulario.fecha_seguimiento,

        titulo:
            formulario.titulo,

        observaciones:
            formulario.observaciones
            || null,

        tratamiento_paciente_id:
            formulario
                .tratamiento_paciente_id
            || null,

        cita_id:
            formulario.cita_id
            || null,
    }

    try {
        if (esEdicion.value) {
            await axios.patch(
                `/clinica/seguimientos/${editandoId.value}`,
                payload
            )
        } else {
            await axios.post(
                urlDatos.value,
                payload
            )
        }

        resetFormulario()

        await cargarSeguimientos()
    } catch (error) {
        if (
            error.response?.status
            === 422
        ) {
            errores.value =
                error.response
                    ?.data
                    ?.errors ?? {}

            return
        }

        errorGeneral.value =
            error.response?.data?.message
            ?? 'No se pudo guardar el seguimiento.'
    } finally {
        guardando.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Helpers presentación
|--------------------------------------------------------------------------
*/

function tratamientoTexto(seguimiento) {
    const tratamiento =
        seguimiento.tratamiento

    if (!tratamiento) {
        return null
    }

    return tratamiento
        .servicio
        ?.nombre
        ?? `Tratamiento #${tratamiento.id}`
}

function citaTexto(cita) {
    if (!cita) {
        return ''
    }

    const estado =
        cita.estado_nombre
        ?? cita.estado
        ?? ''

    return `Cita #${cita.id} · ${mostrarFecha(cita.fecha_hora_inicio)} · ${estado}`
}

function tamanioLegible(bytes) {
    if (
        bytes === null
        || bytes === undefined
    ) {
        return ''
    }

    if (bytes < 1024) {
        return `${bytes} B`
    }

    const kb =
        bytes / 1024

    if (kb < 1024) {
        return `${kb.toFixed(1)} KB`
    }

    const mb =
        kb / 1024

    return `${mb.toFixed(1)} MB`
}

/*
|--------------------------------------------------------------------------
| Fotografías pendientes
|--------------------------------------------------------------------------
*/

function pendientesDe(seguimientoId) {
    return archivosPorSeguimiento[
        seguimientoId
    ] ?? []
}

function seleccionarImagenes(
    seguimientoId,
    event
) {
    errorGeneral.value = ''

    const archivos = Array.from(
        event.target.files ?? []
    )

    const permitidos = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ]

    const maximo =
        15 * 1024 * 1024

    const nuevos = []

    for (const archivo of archivos) {
        if (
            !permitidos.includes(
                archivo.type
            )
        ) {
            errorGeneral.value =
                `${archivo.name}: formato no permitido.`

            continue
        }

        if (
            archivo.size > maximo
        ) {
            errorGeneral.value =
                `${archivo.name}: supera los 15 MB.`

            continue
        }

        const identificador =
            globalThis.crypto
                ?.randomUUID
                ?.()
            ?? `${Date.now()}-${Math.random()}`

        nuevos.push({
            id: identificador,
            archivo,
            descripcion: '',
        })
    }

    archivosPorSeguimiento[
        seguimientoId
    ] = [
        ...pendientesDe(
            seguimientoId
        ),
        ...nuevos,
    ]

    /*
     * Permite seleccionar otra vez
     * el mismo archivo.
     */
    event.target.value = ''
}

function quitarPendiente(
    seguimientoId,
    indice
) {
    archivosPorSeguimiento[
        seguimientoId
    ] = pendientesDe(
        seguimientoId
    ).filter(
        (_, posicion) =>
            posicion !== indice
    )
}

/*
|--------------------------------------------------------------------------
| Subir fotografías
|--------------------------------------------------------------------------
*/

async function subirImagenes(
    seguimiento
) {
    const pendientes =
        pendientesDe(
            seguimiento.id
        )

    if (
        pendientes.length === 0
    ) {
        return
    }

    subiendoSeguimientoId.value =
        seguimiento.id

    errorGeneral.value = ''

    try {
        /*
         * Se envían una por una para evitar
         * solicitudes demasiado grandes.
         */
        for (
            const pendiente
            of pendientes
        ) {
            const formData =
                new FormData()

            formData.append(
                'imagen',
                pendiente.archivo
            )

            if (
                pendiente.descripcion
                    ?.trim()
            ) {
                formData.append(
                    'descripcion',
                    pendiente
                        .descripcion
                        .trim()
                )
            }

            await axios.post(
                `/clinica/seguimientos/${seguimiento.id}/imagenes`,
                formData
            )
        }

        archivosPorSeguimiento[
            seguimiento.id
        ] = []

        await cargarSeguimientos(
            paginacion.value
                ?.current_page ?? 1
        )
    } catch (error) {
        if (
            error.response?.status
            === 422
        ) {
            const mensajes =
                error.response
                    ?.data
                    ?.errors

            errorGeneral.value =
                mensajes?.imagen?.[0]
                ?? 'Una de las imágenes no pudo guardarse.'

            return
        }

        errorGeneral.value =
            error.response
                ?.data
                ?.message
            ?? 'No se pudieron subir las imágenes.'
    } finally {
        subiendoSeguimientoId.value =
            null
    }
}

/*
|--------------------------------------------------------------------------
| Visor
|--------------------------------------------------------------------------
*/

function abrirImagen(imagen) {
    imagenAbierta.value =
        imagen
}

function cerrarImagen() {
    imagenAbierta.value =
        null
}

/*
|--------------------------------------------------------------------------
| Eliminar fotografía
|--------------------------------------------------------------------------
*/

async function eliminarImagen(
    imagen
) {
    const confirmar =
        window.confirm(
            '¿Eliminar esta fotografía clínica? Esta acción no se puede deshacer.'
        )

    if (!confirmar) {
        return
    }

    eliminandoImagenId.value =
        imagen.id

    errorGeneral.value = ''

    try {
        await axios.delete(
            `/clinica/seguimiento-imagenes/${imagen.id}`
        )

        if (
            imagenAbierta.value
                ?.id
            === imagen.id
        ) {
            cerrarImagen()
        }

        await cargarSeguimientos(
            paginacion.value
                ?.current_page ?? 1
        )
    } catch (error) {
        errorGeneral.value =
            error.response
                ?.data
                ?.message
            ?? 'No se pudo eliminar la imagen.'
    } finally {
        eliminandoImagenId.value =
            null
    }
}

/*
|--------------------------------------------------------------------------
| Inicio
|--------------------------------------------------------------------------
*/

onMounted(async () => {
    resetFormulario()

    await cargarSeguimientos()
})
</script>

<template>
    <div
        class="min-h-screen bg-slate-50 p-4 md:p-8"
    >
        <div
            class="mx-auto max-w-6xl space-y-6"
        >
            <!-- ========================================================= -->
            <!-- CABECERA -->
            <!-- ========================================================= -->

            <header
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <p
                    class="text-sm font-semibold uppercase tracking-wide text-slate-500"
                >
                    Seguimiento clínico
                </p>

                <div
                    class="mt-1 flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h1
                            class="text-2xl font-bold text-slate-900"
                        >
                            {{ nombrePaciente }}
                        </h1>

                        <p
                            class="mt-1 text-sm text-slate-500"
                        >
                            {{ paciente.codigo }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl bg-slate-100 px-4 py-2 text-sm text-slate-600"
                    >
                        Evoluciones:
                        <strong
                            class="text-slate-900"
                        >
                            {{ paginacion?.total ?? 0 }}
                        </strong>
                    </div>
                </div>
            </header>

            <!-- ========================================================= -->
            <!-- ERROR -->
            <!-- ========================================================= -->

            <div
                v-if="errorGeneral"
                class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
            >
                {{ errorGeneral }}
            </div>

            <!-- ========================================================= -->
            <!-- FORMULARIO -->
            <!-- ========================================================= -->

            <section
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div
                    class="mb-5 flex items-start justify-between gap-4"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold text-slate-900"
                        >
                            {{
                                esEdicion
                                    ? 'Editar evolución'
                                    : 'Nueva evolución'
                            }}
                        </h2>

                        <p
                            class="mt-1 text-sm text-slate-500"
                        >
                            Registra la evolución clínica del paciente.
                        </p>
                    </div>

                    <button
                        v-if="esEdicion"
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                        @click="resetFormulario"
                    >
                        Cancelar edición
                    </button>
                </div>

                <form
                    class="grid gap-5 md:grid-cols-2"
                    @submit.prevent="guardar"
                >
                    <!-- Fecha -->

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Fecha y hora
                        </label>

                        <input
                            v-model="
                                formulario.fecha_seguimiento
                            "
                            type="datetime-local"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-slate-500"
                        />

                        <p
                            v-if="
                                errores.fecha_seguimiento
                            "
                            class="mt-1 text-xs text-red-600"
                        >
                            {{
                                errores
                                    .fecha_seguimiento[0]
                            }}
                        </p>
                    </div>

                    <!-- Título -->

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Título
                        </label>

                        <input
                            v-model="
                                formulario.titulo
                            "
                            type="text"
                            maxlength="150"
                            placeholder="Ej. Control de ortodoncia"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-slate-500"
                        />

                        <p
                            v-if="
                                errores.titulo
                            "
                            class="mt-1 text-xs text-red-600"
                        >
                            {{
                                errores.titulo[0]
                            }}
                        </p>
                    </div>

                    <!-- Tratamiento -->

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Tratamiento
                        </label>

                        <select
                            v-model="
                                formulario
                                    .tratamiento_paciente_id
                            "
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-slate-500"
                        >
                            <option value="">
                                Sin tratamiento relacionado
                            </option>

                            <option
                                v-for="
                                    tratamiento
                                    in tratamientos
                                "
                                :key="
                                    tratamiento.id
                                "
                                :value="
                                    tratamiento.id
                                "
                            >
                                #{{ tratamiento.id }}
                                ·
                                {{
                                    tratamiento.servicio
                                    ?? 'Tratamiento'
                                }}
                                ·
                                {{
                                    tratamiento.estado
                                }}
                            </option>
                        </select>

                        <p
                            v-if="
                                errores
                                    .tratamiento_paciente_id
                            "
                            class="mt-1 text-xs text-red-600"
                        >
                            {{
                                errores
                                    .tratamiento_paciente_id[0]
                            }}
                        </p>
                    </div>

                    <!-- Cita -->

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Cita relacionada
                        </label>

                        <select
                            v-model="
                                formulario.cita_id
                            "
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-slate-500"
                        >
                            <option value="">
                                Sin cita relacionada
                            </option>

                            <option
                                v-for="
                                    cita
                                    in citas
                                "
                                :key="
                                    cita.id
                                "
                                :value="
                                    cita.id
                                "
                            >
                                {{
                                    citaTexto(cita)
                                }}
                            </option>
                        </select>

                        <p
                            v-if="
                                errores.cita_id
                            "
                            class="mt-1 text-xs text-red-600"
                        >
                            {{
                                errores.cita_id[0]
                            }}
                        </p>
                    </div>

                    <!-- Observaciones -->

                    <div
                        class="md:col-span-2"
                    >
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Observaciones clínicas
                        </label>

                        <textarea
                            v-model="
                                formulario.observaciones
                            "
                            rows="5"
                            maxlength="10000"
                            placeholder="Describe la evolución clínica..."
                            class="w-full resize-y rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-slate-500"
                        />

                        <p
                            v-if="
                                errores.observaciones
                            "
                            class="mt-1 text-xs text-red-600"
                        >
                            {{
                                errores
                                    .observaciones[0]
                            }}
                        </p>
                    </div>

                    <!-- Guardar -->

                    <div
                        class="md:col-span-2 flex justify-end"
                    >
                        <button
                            type="submit"
                            :disabled="
                                guardando
                            "
                            class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{
                                guardando
                                    ? 'Guardando...'
                                    : esEdicion
                                        ? 'Guardar cambios'
                                        : 'Registrar evolución'
                            }}
                        </button>
                    </div>
                </form>
            </section>

            <!-- ========================================================= -->
            <!-- HISTORIAL -->
            <!-- ========================================================= -->

            <section
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div
                    class="mb-6"
                >
                    <h2
                        class="text-lg font-semibold text-slate-900"
                    >
                        Línea de evolución
                    </h2>

                    <p
                        class="mt-1 text-sm text-slate-500"
                    >
                        Historial clínico registrado por el odontólogo.
                    </p>
                </div>

                <!-- Cargando -->

                <div
                    v-if="cargando"
                    class="py-12 text-center text-sm text-slate-500"
                >
                    Cargando seguimiento...
                </div>

                <!-- Vacío -->

                <div
                    v-else-if="
                        seguimientos.length
                        === 0
                    "
                    class="rounded-xl border border-dashed border-slate-300 p-10 text-center"
                >
                    <p
                        class="font-medium text-slate-700"
                    >
                        Todavía no hay evoluciones clínicas.
                    </p>

                    <p
                        class="mt-1 text-sm text-slate-500"
                    >
                        Registra la primera evolución del paciente.
                    </p>
                </div>

                <!-- Línea de tiempo -->

                <div
                    v-else
                    class="space-y-0"
                >
                    <article
                        v-for="
                            (
                                seguimiento,
                                indice
                            )
                            in seguimientos
                        "
                        :key="
                            seguimiento.id
                        "
                        class="relative pl-8"
                    >
                        <!-- Línea vertical -->

                        <div
                            v-if="
                                indice
                                < seguimientos.length - 1
                            "
                            class="absolute left-[7px] top-5 h-full w-px bg-slate-200"
                        />

                        <!-- Punto -->

                        <div
                            class="absolute left-0 top-2 h-4 w-4 rounded-full border-4 border-white bg-slate-900 shadow"
                        />

                        <!-- Tarjeta -->

                        <div
                            class="mb-6 rounded-2xl border border-slate-200 bg-white p-5"
                        >
                            <!-- Cabecera -->

                            <div
                                class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
                            >
                                <div>
                                    <p
                                        class="text-xs font-medium uppercase tracking-wide text-slate-500"
                                    >
                                        {{
                                            mostrarFecha(
                                                seguimiento
                                                    .fecha_seguimiento
                                            )
                                        }}
                                    </p>

                                    <h3
                                        class="mt-1 text-base font-semibold text-slate-900"
                                    >
                                        {{
                                            seguimiento.titulo
                                        }}
                                    </h3>
                                </div>

                                <button
                                    type="button"
                                    class="self-start rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                    @click="
                                        editar(
                                            seguimiento
                                        )
                                    "
                                >
                                    Editar
                                </button>
                            </div>

                            <!-- Observaciones -->

                            <p
                                v-if="
                                    seguimiento.observaciones
                                "
                                class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700"
                            >
                                {{
                                    seguimiento.observaciones
                                }}
                            </p>

                            <!-- Etiquetas -->

                            <div
                                class="mt-4 flex flex-wrap gap-2"
                            >
                                <span
                                    v-if="
                                        tratamientoTexto(
                                            seguimiento
                                        )
                                    "
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600"
                                >
                                    {{
                                        tratamientoTexto(
                                            seguimiento
                                        )
                                    }}
                                </span>

                                <span
                                    v-if="
                                        seguimiento.cita
                                    "
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600"
                                >
                                    Cita
                                    #{{
                                        seguimiento
                                            .cita
                                            .id
                                    }}
                                </span>

                                <span
                                    v-if="
                                        seguimiento.profesional
                                    "
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600"
                                >
                                    Profesional
                                    #{{
                                        seguimiento
                                            .profesional
                                            .id
                                    }}
                                </span>
                            </div>

                            <!-- ================================================= -->
                            <!-- FOTOGRAFÍAS -->
                            <!-- ================================================= -->

                            <div
                                class="mt-5 border-t border-slate-100 pt-5"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <h4
                                            class="text-sm font-semibold text-slate-800"
                                        >
                                            Fotografías clínicas
                                        </h4>

                                        <p
                                            class="mt-0.5 text-xs text-slate-500"
                                        >
                                            {{
                                                seguimiento
                                                    .imagenes
                                                    ?.length
                                                ?? 0
                                            }}
                                            imagen(es)
                                        </p>
                                    </div>

                                    <label
                                        class="cursor-pointer rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Añadir fotos

                                        <input
                                            type="file"
                                            multiple
                                            accept="image/jpeg,image/png,image/webp"
                                            class="hidden"
                                            @change="
                                                seleccionarImagenes(
                                                    seguimiento.id,
                                                    $event
                                                )
                                            "
                                        />
                                    </label>
                                </div>

                                <!-- ============================================= -->
                                <!-- GALERÍA GUARDADA -->
                                <!-- ============================================= -->

                                <div
                                    v-if="
                                        seguimiento
                                            .imagenes
                                            ?.length
                                    "
                                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"
                                >
                                    <div
                                        v-for="
                                            imagen
                                            in seguimiento.imagenes
                                        "
                                        :key="
                                            imagen.id
                                        "
                                        class="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                                    >
                                        <button
                                            type="button"
                                            class="block aspect-square w-full overflow-hidden bg-slate-100"
                                            @click="
                                                abrirImagen(
                                                    imagen
                                                )
                                            "
                                        >
                                            <img
                                                :src="
                                                    imagen
                                                        .miniatura_url
                                                "
                                                :alt="
                                                    imagen.descripcion
                                                    || 'Fotografía clínica'
                                                "
                                                loading="lazy"
                                                class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                            />
                                        </button>

                                        <div
                                            class="p-2"
                                        >
                                            <p
                                                v-if="
                                                    imagen.descripcion
                                                "
                                                class="truncate text-xs text-slate-700"
                                                :title="
                                                    imagen.descripcion
                                                "
                                            >
                                                {{
                                                    imagen.descripcion
                                                }}
                                            </p>

                                            <p
                                                class="mt-0.5 text-[11px] text-slate-400"
                                            >
                                                {{
                                                    tamanioLegible(
                                                        imagen
                                                            .tamanio_bytes
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            :disabled="
                                                eliminandoImagenId
                                                === imagen.id
                                            "
                                            title="Eliminar imagen"
                                            class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white/95 text-sm font-bold text-red-600 shadow hover:bg-red-50 disabled:opacity-50"
                                            @click.stop="
                                                eliminarImagen(
                                                    imagen
                                                )
                                            "
                                        >
                                            {{
                                                eliminandoImagenId
                                                === imagen.id
                                                    ? '…'
                                                    : '×'
                                            }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Sin fotografías -->

                                <div
                                    v-else
                                    class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center"
                                >
                                    <p
                                        class="text-sm text-slate-500"
                                    >
                                        Aún no hay fotografías en esta evolución.
                                    </p>
                                </div>

                                <!-- ============================================= -->
                                <!-- FOTOS PENDIENTES -->
                                <!-- ============================================= -->

                                <div
                                    v-if="
                                        pendientesDe(
                                            seguimiento.id
                                        ).length
                                    "
                                    class="mt-4 space-y-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4"
                                >
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Listas para subir
                                    </p>

                                    <div
                                        v-for="
                                            (
                                                pendiente,
                                                indicePendiente
                                            )
                                            in pendientesDe(
                                                seguimiento.id
                                            )
                                        "
                                        :key="
                                            pendiente.id
                                        "
                                        class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-3 md:flex-row md:items-center"
                                    >
                                        <div
                                            class="min-w-0 flex-1"
                                        >
                                            <p
                                                class="truncate text-sm font-medium text-slate-700"
                                            >
                                                {{
                                                    pendiente
                                                        .archivo
                                                        .name
                                                }}
                                            </p>

                                            <p
                                                class="text-xs text-slate-400"
                                            >
                                                {{
                                                    tamanioLegible(
                                                        pendiente
                                                            .archivo
                                                            .size
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <input
                                            v-model="
                                                pendiente.descripcion
                                            "
                                            type="text"
                                            maxlength="255"
                                            placeholder="Descripción opcional"
                                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-500 md:w-64"
                                        />

                                        <button
                                            type="button"
                                            class="rounded-lg px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50"
                                            @click="
                                                quitarPendiente(
                                                    seguimiento.id,
                                                    indicePendiente
                                                )
                                            "
                                        >
                                            Quitar
                                        </button>
                                    </div>

                                    <div
                                        class="flex justify-end"
                                    >
                                        <button
                                            type="button"
                                            :disabled="
                                                subiendoSeguimientoId
                                                === seguimiento.id
                                            "
                                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                                            @click="
                                                subirImagenes(
                                                    seguimiento
                                                )
                                            "
                                        >
                                            {{
                                                subiendoSeguimientoId
                                                === seguimiento.id
                                                    ? 'Subiendo...'
                                                    : `Subir ${pendientesDe(seguimiento.id).length} foto(s)`
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- ========================================================= -->
                <!-- PAGINACIÓN -->
                <!-- ========================================================= -->

                <div
                    v-if="
                        paginacion
                        && paginacion.last_page > 1
                    "
                    class="mt-6 flex items-center justify-between border-t border-slate-200 pt-5"
                >
                    <button
                        type="button"
                        :disabled="
                            paginacion.current_page
                            <= 1
                        "
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm disabled:opacity-40"
                        @click="
                            cargarSeguimientos(
                                paginacion.current_page - 1
                            )
                        "
                    >
                        Anterior
                    </button>

                    <span
                        class="text-sm text-slate-500"
                    >
                        Página
                        {{
                            paginacion.current_page
                        }}
                        de
                        {{
                            paginacion.last_page
                        }}
                    </span>

                    <button
                        type="button"
                        :disabled="
                            paginacion.current_page
                            >= paginacion.last_page
                        "
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm disabled:opacity-40"
                        @click="
                            cargarSeguimientos(
                                paginacion.current_page + 1
                            )
                        "
                    >
                        Siguiente
                    </button>
                </div>
            </section>
        </div>

        <!-- ============================================================= -->
        <!-- VISOR GRANDE -->
        <!-- FUERA DEL v-for, COMO DEBE SER -->
        <!-- ============================================================= -->

        <Teleport to="body">
            <div
                v-if="
                    imagenAbierta
                "
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4"
                @click.self="
                    cerrarImagen
                "
            >
                <div
                    class="relative flex max-h-[95vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                >
                    <!-- Cabecera visor -->

                    <div
                        class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-3"
                    >
                        <div
                            class="min-w-0"
                        >
                            <p
                                class="truncate text-sm font-semibold text-slate-800"
                            >
                                {{
                                    imagenAbierta.descripcion
                                    || 'Fotografía clínica'
                                }}
                            </p>

                            <p
                                class="text-xs text-slate-500"
                            >
                                {{
                                    imagenAbierta.ancho
                                    ?? '—'
                                }}
                                ×
                                {{
                                    imagenAbierta.alto
                                    ?? '—'
                                }}

                                ·

                                {{
                                    tamanioLegible(
                                        imagenAbierta
                                            .tamanio_bytes
                                    )
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-lg text-xl text-slate-600 hover:bg-slate-100"
                            @click="
                                cerrarImagen
                            "
                        >
                            ×
                        </button>
                    </div>

                    <!-- Imagen -->

                    <div
                        class="flex min-h-0 flex-1 items-center justify-center overflow-auto bg-black p-3"
                    >
                        <img
                            :src="
                                imagenAbierta.url
                            "
                            :alt="
                                imagenAbierta.descripcion
                                || 'Fotografía clínica'
                            "
                            class="max-h-[82vh] max-w-full object-contain"
                        />
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>