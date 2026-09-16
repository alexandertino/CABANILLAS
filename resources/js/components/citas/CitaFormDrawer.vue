<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    X,
    Save,
    LoaderCircle,
    Clock3,
    UserRound,
    UserRoundCheck,
    Stethoscope,
    DoorOpen,
    Search,
    HeartPulse,
    Banknote,
    Timer,
    LockKeyhole,
} from 'lucide-vue-next';

/* =========================================================
   PROPS / EMITS
========================================================= */

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },

    cita: {
        type: Object,
        default: null,
    },

    profesionales: {
        type: Array,
        default: () => [],
    },

    consultorios: {
        type: Array,
        default: () => [],
    },

    estados: {
        type: Array,
        default: () => [],
    },

    estadoPendiente: {
        default: null,
    },
});

const emit = defineEmits([
    'close',
]);

/* =========================================================
   FORMULARIO
========================================================= */

const form = useForm({

    paciente_id: '',
    profesional_id: '',
    consultorio_id: '',
    estado_cita_id: '',
    servicio_id: '',
    fecha_hora_inicio: '',
    fecha_hora_fin: '',

    motivo: '',

    observaciones: '',

    // Tratamiento
    tipo_atencion: 'CITA_SIMPLE',
    tratamiento_paciente_id: '',
    precio_acordado: '',
});

/* =========================================================
   MODO EDICIÓN
========================================================= */

const editando = computed(() => Boolean(props.cita?.id));

/*
 * En la respuesta JSON de Laravel las relaciones pueden llegar
 * en snake_case. También aceptamos camelCase por compatibilidad.
 */
const relacionTratamientoEdicion = computed(() => (
    props.cita?.tratamiento_cita
    ?? props.cita?.tratamientoCita
    ?? null
));

const tratamientoEdicion = computed(() => (
    relacionTratamientoEdicion.value?.tratamiento_paciente
    ?? relacionTratamientoEdicion.value?.tratamientoPaciente
    ?? null
));

const citaConTratamiento = computed(() => (
    editando.value
    &&
    Boolean(relacionTratamientoEdicion.value)
));

/* =========================================================
   ESTADOS
========================================================= */

const estadosEditables = computed(() => {
    const finales = [
        'CANCELADA',
        'ATENDIDA',
        'NO_ASISTIO',
        'REPROGRAMADA',
    ];

    return props.estados.filter(
        estado => !finales.includes(estado.codigo)
    );
});

function obtenerEstadoPendienteId() {
    if (
        props.estadoPendiente
        && typeof props.estadoPendiente === 'object'
    ) {
        return props.estadoPendiente.id ?? '';
    }

    if (props.estadoPendiente) {
        return props.estadoPendiente;
    }

    return props.estados.find(
        estado => estado.codigo === 'PENDIENTE'
    )?.id ?? '';
}

/* =========================================================
   PACIENTES
========================================================= */

const pacienteSeleccionado = ref(null);
const textoPaciente = ref('');
const resultadosPacientes = ref([]);
const buscandoPaciente = ref(false);

let timerPaciente = null;
let peticionPaciente = null;

watch(
    textoPaciente,
    valor => {
        clearTimeout(timerPaciente);

        if (pacienteSeleccionado.value) {
            return;
        }

        const texto = valor.trim();

        if (texto.length < 2) {
            resultadosPacientes.value = [];
            return;
        }

        timerPaciente = setTimeout(
            () => buscarPacientes(texto),
            300
        );
    }
);

async function buscarPacientes(texto) {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        peticionPaciente?.abort();
        peticionPaciente = new AbortController();
        buscandoPaciente.value = true;

        const respuesta = await fetch(
            `/clinica/pacientes/buscar?q=${encodeURIComponent(texto)}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                signal: peticionPaciente.signal,
            }
        );

        if (!respuesta.ok) {
            throw new Error(`HTTP ${respuesta.status}`);
        }

        const datos = await respuesta.json();

        resultadosPacientes.value = Array.isArray(datos)
            ? datos
            : (
                datos.data
                ?? datos.pacientes
                ?? []
            );
    }
    catch (error) {
        if (error.name !== 'AbortError') {
            console.error(
                'Error buscando pacientes:',
                error
            );
        }
    }
    finally {
        buscandoPaciente.value = false;
    }
}

function seleccionarPaciente(paciente) {
    if (citaConTratamiento.value) {
        return;
    }

    pacienteSeleccionado.value = paciente;
    form.paciente_id = paciente.id;
    textoPaciente.value = '';
    resultadosPacientes.value = [];

    form.clearErrors('paciente_id');
}

function quitarPaciente() {
    if (citaConTratamiento.value) {
        return;
    }

    pacienteSeleccionado.value = null;
    form.paciente_id = '';
    textoPaciente.value = '';
    resultadosPacientes.value = [];

    limpiarTratamiento();
    limpiarServicio();
}

/* =========================================================
   SERVICIOS
========================================================= */

const servicioSeleccionado = ref(null);
const textoServicio = ref('');
const resultadosServicios = ref([]);
const buscandoServicio = ref(false);

let timerServicio = null;
let peticionServicio = null;

watch(
    textoServicio,
    valor => {
        clearTimeout(timerServicio);

        if (servicioSeleccionado.value) {
            return;
        }

        if (
            form.tipo_atencion
            === 'CONTINUAR_TRATAMIENTO'
        ) {
            return;
        }

        const texto = valor.trim();

        if (texto.length < 2) {
            resultadosServicios.value = [];
            return;
        }

        timerServicio = setTimeout(
            () => buscarServicios(texto),
            300
        );
    }
);

async function buscarServicios(texto) {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        peticionServicio?.abort();
        peticionServicio = new AbortController();
        buscandoServicio.value = true;

        const respuesta = await fetch(
            `/clinica/servicios/buscar?q=${encodeURIComponent(texto)}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                signal: peticionServicio.signal,
            }
        );

        if (!respuesta.ok) {
            throw new Error(`HTTP ${respuesta.status}`);
        }

        const datos = await respuesta.json();

        resultadosServicios.value = Array.isArray(datos)
            ? datos
            : (
                datos.data
                ?? datos.servicios
                ?? []
            );
    }
    catch (error) {
        if (error.name !== 'AbortError') {
            console.error(
                'Error buscando servicios:',
                error
            );
        }
    }
    finally {
        buscandoServicio.value = false;
    }
}

function seleccionarServicio(servicio) {
    if (
        form.tipo_atencion
        === 'CONTINUAR_TRATAMIENTO'
    ) {
        return;
    }

    servicioSeleccionado.value = servicio;
    form.servicio_id = servicio.id;
    textoServicio.value = '';
    resultadosServicios.value = [];

    if (
        servicio.precio_actual !== undefined
        && servicio.precio_actual !== null
    ) {
        form.precio_acordado = Number(
            servicio.precio_actual
        ).toFixed(2);
    }
    else {
        form.precio_acordado = '';
    }

    form.clearErrors(
        'servicio_id',
        'precio_acordado'
    );

    calcularFin();
}

function limpiarServicio() {
    servicioSeleccionado.value = null;
    form.servicio_id = '';
    form.precio_acordado = '';
    textoServicio.value = '';
    resultadosServicios.value = [];
    form.fecha_hora_fin = '';
    horariosDisponibles.value = [];
    errorHorarios.value = '';
}

function quitarServicio() {
    if (
        citaConTratamiento.value
        ||
        form.tipo_atencion
        === 'CONTINUAR_TRATAMIENTO'
    ) {
        return;
    }

    limpiarServicio();
}

const duracionServicio = computed(() => Number(
    servicioSeleccionado.value?.duracion_estimada_minutos
    ?? 0
));

/* =========================================================
   TRATAMIENTOS
========================================================= */

const tratamientosActivos = ref([]);
const cargandoTratamientos = ref(false);
const errorTratamientos = ref('');

let peticionTratamientos = null;

const tratamientoSeleccionado = computed(() => (
    tratamientosActivos.value.find(
        tratamiento => String(tratamiento.id)
            === String(form.tratamiento_paciente_id)
    ) ?? null
));

function limpiarTratamiento() {
    peticionTratamientos?.abort();

    tratamientosActivos.value = [];
    cargandoTratamientos.value = false;
    errorTratamientos.value = '';

    form.tipo_atencion = 'CITA_SIMPLE';
    form.tratamiento_paciente_id = '';
    form.precio_acordado = '';
}

async function cargarTratamientosPaciente() {
    peticionTratamientos?.abort();

    tratamientosActivos.value = [];
    errorTratamientos.value = '';
    form.tratamiento_paciente_id = '';

    if (!form.paciente_id || editando.value) {
        return;
    }

    if (typeof window === 'undefined') {
        return;
    }

    const controlador = new AbortController();
    peticionTratamientos = controlador;
    cargandoTratamientos.value = true;

    try {
        const respuesta = await fetch(
            `/clinica/pacientes/${form.paciente_id}/tratamientos-activos`,
            {
                headers: {
                    Accept: 'application/json',
                },
                signal: controlador.signal,
            }
        );

        if (!respuesta.ok) {
            throw new Error(`HTTP ${respuesta.status}`);
        }

        const datos = await respuesta.json();

        tratamientosActivos.value = Array.isArray(
            datos.tratamientos
        )
            ? datos.tratamientos
            : [];

        // Mantener CITA_SIMPLE como opción inicial.
        form.tipo_atencion = 'CITA_SIMPLE';
        form.tratamiento_paciente_id = '';
    }
    catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        console.error(
            'Error cargando tratamientos:',
            error
        );

        tratamientosActivos.value = [];
        errorTratamientos.value =
            'No se pudieron consultar los tratamientos del paciente.';
    }
    finally {
        if (peticionTratamientos === controlador) {
            cargandoTratamientos.value = false;
        }
    }
}

function seleccionarCitaSimple() {
    const modoAnterior = form.tipo_atencion;

    form.tipo_atencion = 'CITA_SIMPLE';
    form.tratamiento_paciente_id = '';
    form.precio_acordado = '';

    // Si veníamos de un tratamiento existente, quitamos su servicio
    // para evitar convertirlo accidentalmente en una cita simple.
    if (modoAnterior === 'CONTINUAR_TRATAMIENTO') {
        limpiarServicio();
    }

    form.clearErrors(
        'tipo_atencion',
        'tratamiento_paciente_id',
        'precio_acordado'
    );
}

function activarContinuarTratamiento() {
    if (tratamientosActivos.value.length === 0) {
        return;
    }

    form.tipo_atencion = 'CONTINUAR_TRATAMIENTO';
    form.tratamiento_paciente_id = '';
    form.precio_acordado = '';

    // El tratamiento elegido definirá el servicio y profesional.
    servicioSeleccionado.value = null;
    form.servicio_id = '';
    textoServicio.value = '';
    resultadosServicios.value = [];
    form.fecha_hora_fin = '';
    horariosDisponibles.value = [];

    form.clearErrors(
        'tipo_atencion',
        'tratamiento_paciente_id',
        'precio_acordado',
        'servicio_id'
    );
}

function seleccionarNuevoTratamiento() {
    form.tipo_atencion = 'NUEVO_TRATAMIENTO';
    form.tratamiento_paciente_id = '';

    const precio = servicioSeleccionado.value?.precio_actual;

    form.precio_acordado = (
        precio !== undefined
        && precio !== null
    )
        ? Number(precio).toFixed(2)
        : '';

    form.clearErrors(
        'tipo_atencion',
        'tratamiento_paciente_id',
        'precio_acordado',
        'servicio_id'
    );
}

function seleccionarTratamiento(tratamiento) {
    if (!tratamiento?.id) {
        return;
    }

    form.tipo_atencion = 'CONTINUAR_TRATAMIENTO';
    form.tratamiento_paciente_id = tratamiento.id;

    form.servicio_id = tratamiento.servicio_id;
    servicioSeleccionado.value = tratamiento.servicio ?? null;
    textoServicio.value = '';
    resultadosServicios.value = [];

    if (tratamiento.profesional_id) {
        form.profesional_id = tratamiento.profesional_id;
    }

    // El precio ya pertenece al tratamiento original.
    form.precio_acordado = '';

    form.clearErrors(
        'tipo_atencion',
        'tratamiento_paciente_id',
        'precio_acordado',
        'servicio_id'
    );

    calcularFin();
}

watch(
    () => form.paciente_id,
    (nuevoPaciente, pacienteAnterior) => {
        if (editando.value) {
            return;
        }

        if (
            String(nuevoPaciente ?? '')
            === String(pacienteAnterior ?? '')
        ) {
            return;
        }

        // Evita arrastrar servicio/tratamiento del paciente anterior.
        form.tipo_atencion = 'CITA_SIMPLE';
        form.tratamiento_paciente_id = '';
        form.precio_acordado = '';

        servicioSeleccionado.value = null;
        form.servicio_id = '';
        textoServicio.value = '';
        resultadosServicios.value = [];
        form.fecha_hora_fin = '';
        horariosDisponibles.value = [];

        if (nuevoPaciente) {
            cargarTratamientosPaciente();
        }
        else {
            tratamientosActivos.value = [];
            errorTratamientos.value = '';
        }
    }
);

/* =========================================================
   FECHAS / DURACIÓN
========================================================= */

function fechaParaInput(fecha) {
    const year = fecha.getFullYear();
    const month = String(
        fecha.getMonth() + 1
    ).padStart(2, '0');
    const day = String(
        fecha.getDate()
    ).padStart(2, '0');
    const hour = String(
        fecha.getHours()
    ).padStart(2, '0');
    const minute = String(
        fecha.getMinutes()
    ).padStart(2, '0');

    return `${year}-${month}-${day}T${hour}:${minute}`;
}

function normalizarFechaHora(valor) {
    if (!valor) {
        return '';
    }

    return String(valor)
        .replace(' ', 'T')
        .slice(0, 16);
}

function calcularFin() {
    if (
        !form.fecha_hora_inicio
        || duracionServicio.value <= 0
    ) {
        form.fecha_hora_fin = '';
        return;
    }

    const inicio = new Date(
        `${form.fecha_hora_inicio}:00`
    );

    if (Number.isNaN(inicio.getTime())) {
        form.fecha_hora_fin = '';
        return;
    }

    inicio.setMinutes(
        inicio.getMinutes()
        + duracionServicio.value
    );

    form.fecha_hora_fin = fechaParaInput(inicio);
}

watch(
    [
        () => form.fecha_hora_inicio,
        duracionServicio,
    ],
    calcularFin
);

function formatoPrecio(valor) {
    return new Intl.NumberFormat(
        'es-PE',
        {
            style: 'currency',
            currency: 'PEN',
        }
    ).format(Number(valor ?? 0));
}

function dinero(valor) {
    return formatoPrecio(valor);
}

function formatoDuracion(minutos) {
    const total = Number(minutos ?? 0);

    if (total < 60) {
        return `${total} min`;
    }

    const horas = Math.floor(total / 60);
    const resto = total % 60;

    if (resto === 0) {
        return `${horas} h`;
    }

    return `${horas} h ${resto} min`;
}

function horaFinal() {
    if (!form.fecha_hora_fin) {
        return '--:--';
    }

    return form.fecha_hora_fin.split('T')[1] ?? '--:--';
}

/* =========================================================
   DISPONIBILIDAD
========================================================= */

const horariosDisponibles = ref([]);
const cargandoHorarios = ref(false);
const errorHorarios = ref('');

let controladorHorarios = null;
let timerHorarios = null;

function fechaSeleccionada() {
    if (!form.fecha_hora_inicio) {
        return '';
    }

    const fecha = String(
        form.fecha_hora_inicio
    ).slice(0, 10);

    return /^\d{4}-\d{2}-\d{2}$/.test(fecha)
        ? fecha
        : '';
}

async function cargarHorariosDisponibles() {
    const fecha = fechaSeleccionada();

    if (
        !fecha
        || !form.profesional_id
        || !form.servicio_id
    ) {
        horariosDisponibles.value = [];
        errorHorarios.value = '';
        return;
    }

    if (typeof window === 'undefined') {
        return;
    }

    controladorHorarios?.abort();
    controladorHorarios = new AbortController();
    cargandoHorarios.value = true;
    errorHorarios.value = '';

    try {
        const parametros = new URLSearchParams();

        parametros.set('fecha', fecha);
        parametros.set(
            'profesional_id',
            form.profesional_id
        );
        parametros.set(
            'servicio_id',
            form.servicio_id
        );

        if (form.consultorio_id) {
            parametros.set(
                'consultorio_id',
                form.consultorio_id
            );
        }

        if (props.cita?.id) {
            parametros.set(
                'cita_id',
                props.cita.id
            );
        }

        const respuesta = await fetch(
            `/clinica/citas/disponibilidad?${parametros.toString()}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                signal: controladorHorarios.signal,
            }
        );

        if (!respuesta.ok) {
            const texto = await respuesta.text();

            console.error(
                'Respuesta disponibilidad:',
                texto
            );

            throw new Error(`HTTP ${respuesta.status}`);
        }

        const datos = await respuesta.json();

        horariosDisponibles.value = datos.horarios ?? [];
    }
    catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        console.error(
            'Error consultando horarios:',
            error
        );

        horariosDisponibles.value = [];
        errorHorarios.value =
            'No se pudieron consultar los horarios disponibles.';
    }
    finally {
        cargandoHorarios.value = false;
    }
}

watch(
    () => [
        form.profesional_id,
        form.consultorio_id,
        form.servicio_id,
        fechaSeleccionada(),
    ],
    () => {
        clearTimeout(timerHorarios);

        timerHorarios = setTimeout(
            cargarHorariosDisponibles,
            250
        );
    }
);

function seleccionarHorario(horario) {
    form.fecha_hora_inicio = horario.inicio;
    calcularFin();
    form.clearErrors('fecha_hora_inicio');
}

/* =========================================================
   CARGAR NUEVA CITA / EDICIÓN
========================================================= */

function cargar() {
    form.clearErrors();

    textoPaciente.value = '';
    textoServicio.value = '';
    resultadosPacientes.value = [];
    resultadosServicios.value = [];
    horariosDisponibles.value = [];
    errorHorarios.value = '';
    tratamientosActivos.value = [];
    cargandoTratamientos.value = false;
    errorTratamientos.value = '';

    if (!props.cita) {
        form.reset();
        form.estado_cita_id = obtenerEstadoPendienteId();
        form.tipo_atencion = 'CITA_SIMPLE';
        form.tratamiento_paciente_id = '';
        form.precio_acordado = '';

        pacienteSeleccionado.value = null;
        servicioSeleccionado.value = null;
        return;
    }

    form.paciente_id =
        props.cita.paciente_id
        ?? props.cita.paciente?.id
        ?? '';

    pacienteSeleccionado.value =
        props.cita.paciente
        ?? null;

    form.profesional_id =
        props.cita.profesional_id
        ?? props.cita.profesional?.id
        ?? '';

    form.consultorio_id =
        props.cita.consultorio_id
        ?? props.cita.consultorio?.id
        ?? '';

    form.estado_cita_id =
        props.cita.estado_cita_id
        ?? props.cita.estado_cita?.id
        ?? obtenerEstadoPendienteId();

    const relacionServicio =
        props.cita.servicios_cita?.[0]
        ?? null;

    const tratamientoActual =
        tratamientoEdicion.value;

    /*
     * Si la cita pertenece a un tratamiento, el servicio del
     * tratamiento es la referencia clínica principal.
     */
    servicioSeleccionado.value =
        tratamientoActual?.servicio
        ?? relacionServicio?.servicio
        ?? null;

    form.servicio_id =
        tratamientoActual?.servicio_id
        ?? tratamientoActual?.servicio?.id
        ?? relacionServicio?.servicio_id
        ?? relacionServicio?.servicio?.id
        ?? '';

    form.fecha_hora_inicio = normalizarFechaHora(
        props.cita.fecha_hora_inicio
    );

    form.fecha_hora_fin = normalizarFechaHora(
        props.cita.fecha_hora_fin
    );

    form.motivo = props.cita.motivo ?? '';
    form.observaciones = props.cita.observaciones ?? '';

    // El tratamiento no se cambia desde la edición normal.
    form.tipo_atencion =
        citaConTratamiento.value
            ? 'CONTINUAR_TRATAMIENTO'
            : 'CITA_SIMPLE';

    form.tratamiento_paciente_id =
        citaConTratamiento.value
            ? (
                tratamientoEdicion.value?.id
                ?? ''
            )
            : '';

    form.precio_acordado = '';

    calcularFin();
}

watch(
    [
        () => props.open,
        () => props.cita,
    ],
    ([abierto]) => {
        if (abierto) {
            cargar();
        }
    },
    {
        immediate: true,
    }
);

/* =========================================================
   CERRAR
========================================================= */

function cerrar() {
    if (form.processing) {
        return;
    }

    peticionPaciente?.abort();
    peticionServicio?.abort();
    peticionTratamientos?.abort();
    controladorHorarios?.abort();

    clearTimeout(timerPaciente);
    clearTimeout(timerServicio);
    clearTimeout(timerHorarios);

    emit('close');
}

/* =========================================================
   VALIDAR TRATAMIENTO
========================================================= */

function validarTratamiento() {
    if (editando.value) {
        return true;
    }

    if (!form.tipo_atencion) {
        form.setError(
            'tipo_atencion',
            'Selecciona el tipo de atención.'
        );
        return false;
    }

    if (form.tipo_atencion === 'CITA_SIMPLE') {
        return true;
    }

    if (
        form.tipo_atencion
        === 'NUEVO_TRATAMIENTO'
    ) {
        if (
            form.precio_acordado === ''
            || form.precio_acordado === null
            || Number.isNaN(
                Number(form.precio_acordado)
            )
            || Number(form.precio_acordado) < 0
        ) {
            form.setError(
                'precio_acordado',
                'Ingresa un precio acordado válido.'
            );
            return false;
        }

        return true;
    }

    if (
        form.tipo_atencion
        === 'CONTINUAR_TRATAMIENTO'
    ) {
        if (!form.tratamiento_paciente_id) {
            form.setError(
                'tratamiento_paciente_id',
                'Selecciona el tratamiento que continuará.'
            );
            return false;
        }

        return true;
    }

    form.setError(
        'tipo_atencion',
        'El tipo de atención seleccionado no es válido.'
    );

    return false;
}

/* =========================================================
   GUARDAR
========================================================= */

function guardar() {
    form.clearErrors();

    let hayError = false;

    if (!form.paciente_id) {
        form.setError(
            'paciente_id',
            'Selecciona un paciente.'
        );
        hayError = true;
    }

    if (!form.servicio_id) {
        form.setError(
            'servicio_id',
            'Selecciona un servicio.'
        );
        hayError = true;
    }

    if (!form.profesional_id) {
        form.setError(
            'profesional_id',
            'Selecciona un profesional.'
        );
        hayError = true;
    }

    if (!form.fecha_hora_inicio) {
        form.setError(
            'fecha_hora_inicio',
            'Selecciona la fecha y hora.'
        );
        hayError = true;
    }

    if (!validarTratamiento()) {
        hayError = true;
    }

    if (hayError) {
        return;
    }

    calcularFin();

    if (editando.value) {
        form.transform(datos => {
            const payload = {
                ...datos,
            };

            delete payload.tipo_atencion;
            delete payload.tratamiento_paciente_id;
            delete payload.precio_acordado;

            /*
             * Aunque la interfaz ya está bloqueada, reenviamos los IDs
             * originales para que la petición sea coherente.
             */
            if (citaConTratamiento.value) {
                payload.paciente_id =
                    props.cita.paciente_id
                    ?? props.cita.paciente?.id
                    ?? payload.paciente_id;

                payload.servicio_id =
                    tratamientoEdicion.value?.servicio_id
                    ?? tratamientoEdicion.value?.servicio?.id
                    ?? props.cita.servicios_cita?.[0]?.servicio_id
                    ?? payload.servicio_id;
            }

            return payload;
        });

        form.put(
            `/clinica/citas/${props.cita.id}`,
            {
                preserveScroll: true,

                onSuccess: () => {
                    emit('close');
                },

                onError: errores => {
                    console.error(
                        'Errores al editar cita:',
                        errores
                    );
                },

                onFinish: () => {
                    form.transform(datos => datos);
                },
            }
        );

        return;
    }

    form.transform(datos => {
        const payload = {
            ...datos,
        };

        if (payload.tipo_atencion === 'CITA_SIMPLE') {
            payload.tratamiento_paciente_id = null;
            payload.precio_acordado = null;
        }

        if (
            payload.tipo_atencion
            === 'NUEVO_TRATAMIENTO'
        ) {
            payload.tratamiento_paciente_id = null;
        }

        if (
            payload.tipo_atencion
            === 'CONTINUAR_TRATAMIENTO'
        ) {
            payload.precio_acordado = null;
        }

        return payload;
    });

    form.post(
        '/clinica/citas',
        {
            preserveScroll: true,

            onSuccess: () => {
                form.reset();
                pacienteSeleccionado.value = null;
                servicioSeleccionado.value = null;
                tratamientosActivos.value = [];
                horariosDisponibles.value = [];

                emit('close');
            },

            onError: errores => {
                console.error(
                    'Errores al crear cita:',
                    errores
                );
            },

            onFinish: () => {
                form.transform(datos => datos);
            },
        }
    );
}
</script>

<template>

    <div>

        <!-- =====================================================
             FONDO
        ====================================================== -->

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
                @click="cerrar"
            />

        </Transition>


        <!-- =====================================================
             DRAWER
        ====================================================== -->

        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            leave-active-class="transition-transform duration-200 ease-in"
            enter-from-class="translate-x-full"
            leave-to-class="translate-x-full"
        >

            <aside
                v-if="open"
                class="
                    fixed
                    inset-y-0
                    right-0
                    z-[100]
                    flex
                    w-full
                    max-w-2xl
                    flex-col
                    border-l
                    border-slate-200
                    bg-white
                    shadow-2xl
                "
            >

                <!-- =================================================
                     HEADER
                ================================================== -->

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
                                items-center
                                justify-center
                                rounded-xl
                                bg-clinica-50
                                text-clinica-700
                            "
                        >

                            <CalendarDays
                                :size="21"
                            />

                        </div>


                        <div>

                            <h2
                                class="
                                    text-lg
                                    font-bold
                                    text-slate-900
                                "
                            >
                                {{
                                    editando
                                        ? 'Editar cita'
                                        : 'Nueva cita'
                                }}
                            </h2>


                            <p
                                class="
                                    mt-0.5
                                    text-xs
                                    text-slate-500
                                "
                            >
                                Programa una atención odontológica
                            </p>

                        </div>

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
                            transition
                            hover:bg-slate-100
                            hover:text-slate-700
                        "
                        @click="cerrar"
                    >

                        <X :size="20" />

                    </button>

                </header>


                <!-- =================================================
                     FORMULARIO
                ================================================== -->

                <form
                    class="
                        flex
                        min-h-0
                        flex-1
                        flex-col
                    "
                    @submit.prevent="guardar"
                >

                    <div
                        class="
                            flex-1
                            space-y-7
                            overflow-y-auto
                            p-6
                        "
                    >

                        <!-- =============================================
                             PACIENTE
                        ============================================== -->

                        <section>

                            <label
                                class="
                                    form-label
                                    flex
                                    items-center
                                    gap-2
                                "
                            >

                                <UserRound
                                    :size="15"
                                />

                                Paciente

                            </label>


                            <!-- PACIENTE ELEGIDO -->

                            <div
                                v-if="
                                    pacienteSeleccionado
                                "
                                class="
                                    flex
                                    items-center
                                    gap-3
                                    rounded-2xl
                                    border
                                    border-clinica-200
                                    bg-clinica-50/50
                                    p-4
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
                                        bg-white
                                        text-clinica-700
                                    "
                                >

                                    <UserRoundCheck
                                        :size="20"
                                    />

                                </div>


                                <div
                                    class="
                                        min-w-0
                                        flex-1
                                    "
                                >

                                    <p
                                        class="
                                            truncate
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        {{
                                            pacienteSeleccionado
                                                .nombres
                                        }}

                                        {{
                                            pacienteSeleccionado
                                                .apellidos
                                        }}
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                        {{
                                            pacienteSeleccionado
                                                .codigo
                                            ??
                                            'Sin código'
                                        }}

                                        <span
                                            v-if="
                                                pacienteSeleccionado
                                                    .numero_documento
                                            "
                                        >
                                            ·
                                            {{
                                                pacienteSeleccionado
                                                    .numero_documento
                                            }}
                                        </span>
                                    </p>

                                </div>


                                <button
                                    v-if="
                                        !citaConTratamiento
                                    "
                                    type="button"
                                    class="
                                        flex
                                        h-9
                                        w-9
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-lg
                                        text-slate-400
                                        transition
                                        hover:bg-white
                                        hover:text-rose-600
                                    "
                                    @click="
                                        quitarPaciente
                                    "
                                >

                                    <X :size="17" />

                                </button>

                            </div>


                            <div
                                v-if="
                                    citaConTratamiento
                                    &&
                                    pacienteSeleccionado
                                "
                                class="
                                    mt-2
                                    flex
                                    items-start
                                    gap-2
                                    rounded-xl
                                    bg-violet-50
                                    px-3
                                    py-2.5
                                    text-xs
                                    text-violet-700
                                "
                            >
                                <LockKeyhole
                                    :size="14"
                                    class="mt-0.5 shrink-0"
                                />

                                <span>
                                    Paciente bloqueado: esta cita pertenece a un
                                    tratamiento y no puede trasladarse a otro paciente.
                                </span>
                            </div>


                            <!-- BUSCADOR -->

                            <div
                                v-if="
                                    !pacienteSeleccionado
                                    &&
                                    !citaConTratamiento
                                "
                                class="relative"
                            >

                                <Search
                                    :size="18"
                                    class="
                                        absolute
                                        left-3.5
                                        top-3.5
                                        text-slate-400
                                    "
                                />


                                <input
                                    v-model="
                                        textoPaciente
                                    "
                                    type="search"
                                    autocomplete="off"
                                    class="
                                        input-clinica
                                        pl-10
                                        pr-10
                                    "
                                    placeholder="Nombre, DNI o código..."
                                >


                                <LoaderCircle
                                    v-if="
                                        buscandoPaciente
                                    "
                                    :size="17"
                                    class="
                                        absolute
                                        right-3.5
                                        top-3.5
                                        animate-spin
                                        text-clinica-600
                                    "
                                />


                                <div
                                    v-if="
                                        textoPaciente
                                            .trim()
                                            .length >= 2
                                    "
                                    class="
                                        mt-2
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        shadow-lg
                                    "
                                >

                                    <button
                                        v-for="
                                            paciente
                                            in resultadosPacientes
                                        "
                                        :key="
                                            paciente.id
                                        "
                                        type="button"
                                        class="
                                            flex
                                            w-full
                                            items-center
                                            gap-3
                                            border-b
                                            border-slate-100
                                            px-4
                                            py-3
                                            text-left
                                            transition
                                            last:border-0
                                            hover:bg-slate-50
                                        "
                                        @click="
                                            seleccionarPaciente(
                                                paciente
                                            )
                                        "
                                    >

                                        <UserRound
                                            :size="18"
                                            class="
                                                text-slate-400
                                            "
                                        />


                                        <div>

                                            <p
                                                class="
                                                    text-sm
                                                    font-semibold
                                                    text-slate-800
                                                "
                                            >
                                                {{
                                                    paciente.nombres
                                                }}

                                                {{
                                                    paciente.apellidos
                                                }}
                                            </p>


                                            <p
                                                class="
                                                    mt-1
                                                    text-xs
                                                    text-slate-400
                                                "
                                            >
                                                {{
                                                    paciente.codigo
                                                    ??
                                                    'Sin código'
                                                }}

                                                <span
                                                    v-if="
                                                        paciente
                                                            .numero_documento
                                                    "
                                                >
                                                    ·
                                                    {{
                                                        paciente
                                                            .numero_documento
                                                    }}
                                                </span>
                                            </p>

                                        </div>

                                    </button>


                                    <p
                                        v-if="
                                            !buscandoPaciente
                                            &&
                                            resultadosPacientes
                                                .length === 0
                                        "
                                        class="
                                            px-4
                                            py-5
                                            text-center
                                            text-sm
                                            text-slate-400
                                        "
                                    >
                                        No encontramos pacientes.
                                    </p>

                                </div>

                            </div>


                            <p
                                v-if="
                                    form.errors
                                        .paciente_id
                                "
                                class="error-text"
                            >
                                {{
                                    form.errors
                                        .paciente_id
                                }}
                            </p>

                        </section>


                        <!-- =============================================
                             TIPO DE ATENCIÓN
                        ============================================== -->

                        <section
                            v-if="
                                form.paciente_id
                                &&
                                !editando
                            "
                        >

                            <div
                                class="
                                    mb-4
                                    flex
                                    items-start
                                    justify-between
                                    gap-3
                                "
                            >

                                <div>

                                    <label
                                        class="
                                            form-label
                                            flex
                                            items-center
                                            gap-2
                                        "
                                    >

                                        <HeartPulse :size="15" />

                                        Tipo de atención

                                    </label>

                                    <p
                                        class="
                                            mt-1
                                            text-xs
                                            leading-5
                                            text-slate-500
                                        "
                                    >
                                        Elige si es una cita independiente,
                                        un tratamiento nuevo o una sesión de uno existente.
                                    </p>

                                </div>

                                <LoaderCircle
                                    v-if="cargandoTratamientos"
                                    :size="18"
                                    class="
                                        shrink-0
                                        animate-spin
                                        text-clinica-600
                                    "
                                />

                            </div>

                            <div class="space-y-3">

                                <!-- CITA SIMPLE -->

                                <button
                                    type="button"
                                    class="
                                        w-full
                                        rounded-2xl
                                        border
                                        p-4
                                        text-left
                                        transition
                                    "
                                    :class="
                                        form.tipo_atencion === 'CITA_SIMPLE'
                                            ? [
                                                'border-clinica-500',
                                                'bg-clinica-50',
                                                'ring-1',
                                                'ring-clinica-200',
                                            ]
                                            : [
                                                'border-slate-200',
                                                'bg-white',
                                                'hover:border-clinica-200',
                                                'hover:bg-slate-50',
                                            ]
                                    "
                                    @click="seleccionarCitaSimple"
                                >

                                    <div class="flex items-start gap-3">

                                        <div
                                            class="
                                                mt-0.5
                                                flex
                                                h-5
                                                w-5
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-full
                                                border-2
                                            "
                                            :class="
                                                form.tipo_atencion === 'CITA_SIMPLE'
                                                    ? 'border-clinica-600'
                                                    : 'border-slate-300'
                                            "
                                        >
                                            <div
                                                v-if="form.tipo_atencion === 'CITA_SIMPLE'"
                                                class="h-2.5 w-2.5 rounded-full bg-clinica-600"
                                            />
                                        </div>

                                        <div>
                                            <p class="text-sm font-bold text-slate-900">
                                                Cita sin tratamiento
                                            </p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                Consulta, evaluación, control u otra atención independiente.
                                            </p>
                                        </div>

                                    </div>

                                </button>

                                <!-- NUEVO TRATAMIENTO -->

                                <button
                                    type="button"
                                    class="
                                        w-full
                                        rounded-2xl
                                        border
                                        p-4
                                        text-left
                                        transition
                                    "
                                    :class="
                                        form.tipo_atencion === 'NUEVO_TRATAMIENTO'
                                            ? [
                                                'border-violet-500',
                                                'bg-violet-50',
                                                'ring-1',
                                                'ring-violet-200',
                                            ]
                                            : [
                                                'border-slate-200',
                                                'bg-white',
                                                'hover:border-violet-200',
                                                'hover:bg-violet-50/30',
                                            ]
                                    "
                                    @click="seleccionarNuevoTratamiento"
                                >

                                    <div class="flex items-start gap-3">

                                        <div
                                            class="
                                                mt-0.5
                                                flex
                                                h-5
                                                w-5
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-full
                                                border-2
                                            "
                                            :class="
                                                form.tipo_atencion === 'NUEVO_TRATAMIENTO'
                                                    ? 'border-violet-600'
                                                    : 'border-slate-300'
                                            "
                                        >
                                            <div
                                                v-if="form.tipo_atencion === 'NUEVO_TRATAMIENTO'"
                                                class="h-2.5 w-2.5 rounded-full bg-violet-600"
                                            />
                                        </div>

                                        <div>
                                            <p class="text-sm font-bold text-slate-900">
                                                Nuevo tratamiento
                                            </p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                Esta cita será la primera sesión de un nuevo tratamiento.
                                            </p>
                                        </div>

                                    </div>

                                </button>

                                <!-- CONTINUAR TRATAMIENTO -->

                                <button
                                    type="button"
                                    :disabled="
                                        tratamientosActivos.length === 0
                                        || cargandoTratamientos
                                    "
                                    class="
                                        w-full
                                        rounded-2xl
                                        border
                                        p-4
                                        text-left
                                        transition
                                        disabled:cursor-not-allowed
                                        disabled:opacity-50
                                    "
                                    :class="
                                        form.tipo_atencion === 'CONTINUAR_TRATAMIENTO'
                                            ? [
                                                'border-amber-500',
                                                'bg-amber-50',
                                                'ring-1',
                                                'ring-amber-200',
                                            ]
                                            : [
                                                'border-slate-200',
                                                'bg-white',
                                                'hover:border-amber-200',
                                                'hover:bg-amber-50/30',
                                            ]
                                    "
                                    @click="activarContinuarTratamiento"
                                >

                                    <div class="flex items-start gap-3">

                                        <div
                                            class="
                                                mt-0.5
                                                flex
                                                h-5
                                                w-5
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-full
                                                border-2
                                            "
                                            :class="
                                                form.tipo_atencion === 'CONTINUAR_TRATAMIENTO'
                                                    ? 'border-amber-600'
                                                    : 'border-slate-300'
                                            "
                                        >
                                            <div
                                                v-if="form.tipo_atencion === 'CONTINUAR_TRATAMIENTO'"
                                                class="h-2.5 w-2.5 rounded-full bg-amber-600"
                                            />
                                        </div>

                                        <div>
                                            <p class="text-sm font-bold text-slate-900">
                                                Continuar tratamiento existente
                                            </p>

                                            <p
                                                v-if="tratamientosActivos.length"
                                                class="mt-1 text-xs leading-5 text-slate-500"
                                            >
                                                Hay {{ tratamientosActivos.length }} tratamiento(s) activo(s).
                                            </p>

                                            <p
                                                v-else
                                                class="mt-1 text-xs leading-5 text-slate-400"
                                            >
                                                Este paciente no tiene tratamientos activos.
                                            </p>
                                        </div>

                                    </div>

                                </button>

                            </div>

                            <!-- LISTA DE TRATAMIENTOS -->

                            <div
                                v-if="
                                    form.tipo_atencion === 'CONTINUAR_TRATAMIENTO'
                                    && tratamientosActivos.length
                                "
                                class="mt-4 space-y-2"
                            >

                                <p
                                    class="
                                        text-[11px]
                                        font-bold
                                        uppercase
                                        tracking-wide
                                        text-slate-400
                                    "
                                >
                                    Selecciona el tratamiento
                                </p>

                                <button
                                    v-for="tratamiento in tratamientosActivos"
                                    :key="tratamiento.id"
                                    type="button"
                                    class="
                                        w-full
                                        rounded-2xl
                                        border
                                        p-4
                                        text-left
                                        transition
                                    "
                                    :class="
                                        String(form.tratamiento_paciente_id)
                                        === String(tratamiento.id)
                                            ? [
                                                'border-violet-500',
                                                'bg-violet-50',
                                                'ring-1',
                                                'ring-violet-200',
                                            ]
                                            : [
                                                'border-slate-200',
                                                'bg-white',
                                                'hover:border-violet-200',
                                            ]
                                    "
                                    @click="seleccionarTratamiento(tratamiento)"
                                >

                                    <div class="flex items-start justify-between gap-4">

                                        <div class="min-w-0 flex-1">

                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="truncate text-sm font-bold text-slate-900">
                                                    {{ tratamiento.servicio?.nombre ?? 'Tratamiento' }}
                                                </p>
                                                <span
                                                    class="
                                                        rounded-full
                                                        bg-violet-100
                                                        px-2
                                                        py-0.5
                                                        text-[10px]
                                                        font-bold
                                                        text-violet-700
                                                    "
                                                >
                                                    {{ tratamiento.estado }}
                                                </span>
                                            </div>

                                            <p class="mt-2 text-xs text-slate-500">
                                                Programadas:
                                                {{ tratamiento.sesiones_programadas ?? tratamiento.numero_sesiones ?? 0 }}
                                                · Realizadas:
                                                {{ tratamiento.sesiones_realizadas ?? 0 }}
                                                · Próxima:
                                                sesión {{ tratamiento.proxima_sesion ?? 1 }}
                                            </p>

                                            <div
                                                v-if="
                                                    (tratamiento.sesiones_no_asistio ?? 0) > 0
                                                    ||
                                                    (tratamiento.sesiones_canceladas ?? 0) > 0
                                                "
                                                class="
                                                    mt-2
                                                    flex
                                                    flex-wrap
                                                    gap-2
                                                "
                                            >
                                                <span
                                                    v-if="
                                                        (tratamiento.sesiones_no_asistio ?? 0) > 0
                                                    "
                                                    class="
                                                        rounded-full
                                                        bg-slate-100
                                                        px-2
                                                        py-1
                                                        text-[10px]
                                                        font-bold
                                                        text-slate-600
                                                    "
                                                >
                                                    No asistió:
                                                    {{ tratamiento.sesiones_no_asistio }}
                                                </span>

                                                <span
                                                    v-if="
                                                        (tratamiento.sesiones_canceladas ?? 0) > 0
                                                    "
                                                    class="
                                                        rounded-full
                                                        bg-rose-50
                                                        px-2
                                                        py-1
                                                        text-[10px]
                                                        font-bold
                                                        text-rose-600
                                                    "
                                                >
                                                    Canceladas:
                                                    {{ tratamiento.sesiones_canceladas }}
                                                </span>
                                            </div>

                                            <p
                                                v-if="tratamiento.profesional"
                                                class="mt-1 text-xs text-slate-400"
                                            >
                                                Profesional:
                                                {{ tratamiento.profesional.nombres }}
                                                {{ tratamiento.profesional.apellidos }}
                                            </p>

                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p class="text-[10px] font-medium text-slate-400">
                                                Saldo
                                            </p>
                                            <p class="mt-1 text-sm font-bold text-amber-600">
                                                {{ dinero(tratamiento.saldo) }}
                                            </p>
                                        </div>

                                    </div>

                                    <div class="mt-4 grid grid-cols-3 gap-2">

                                        <div class="rounded-xl bg-white/80 px-3 py-2.5">
                                            <p class="text-[10px] text-slate-400">Precio</p>
                                            <p class="mt-1 text-xs font-bold text-slate-700">
                                                {{ dinero(tratamiento.total) }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-white/80 px-3 py-2.5">
                                            <p class="text-[10px] text-slate-400">Pagado</p>
                                            <p class="mt-1 text-xs font-bold text-emerald-600">
                                                {{ dinero(tratamiento.pagado) }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-white/80 px-3 py-2.5">
                                            <p class="text-[10px] text-slate-400">Estado pago</p>
                                            <p class="mt-1 text-xs font-bold text-slate-700">
                                                {{ tratamiento.estado_pago }}
                                            </p>
                                        </div>

                                    </div>

                                </button>

                            </div>

                            <p
                                v-if="errorTratamientos"
                                class="
                                    mt-3
                                    rounded-xl
                                    bg-rose-50
                                    p-3
                                    text-xs
                                    text-rose-600
                                "
                            >
                                {{ errorTratamientos }}
                            </p>

                            <p
                                v-if="form.errors.tipo_atencion"
                                class="error-text"
                            >
                                {{ form.errors.tipo_atencion }}
                            </p>

                            <p
                                v-if="form.errors.tratamiento_paciente_id"
                                class="error-text"
                            >
                                {{ form.errors.tratamiento_paciente_id }}
                            </p>

                        </section>


                        <!-- =============================================
                             SERVICIO
                        ============================================== -->

                        <section>

                            <label
                                class="
                                    form-label
                                    flex
                                    items-center
                                    gap-2
                                "
                            >

                                <HeartPulse
                                    :size="15"
                                />

                                Servicio

                            </label>


                            <!-- SERVICIO ELEGIDO -->

                            <div
                                v-if="
                                    servicioSeleccionado
                                "
                                class="
                                    rounded-2xl
                                    border
                                    border-clinica-200
                                    bg-clinica-50/50
                                    p-4
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

                                    <div>

                                        <p
                                            class="
                                                text-sm
                                                font-bold
                                                text-slate-900
                                            "
                                        >
                                            {{
                                                servicioSeleccionado
                                                    .nombre
                                            }}
                                        </p>


                                        <div
                                            class="
                                                mt-2
                                                flex
                                                flex-wrap
                                                gap-4
                                                text-xs
                                            "
                                        >

                                            <span
                                                class="
                                                    inline-flex
                                                    items-center
                                                    gap-1
                                                    text-slate-500
                                                "
                                            >

                                                <Clock3
                                                    :size="13"
                                                />

                                                {{
                                                    formatoDuracion(
                                                        duracionServicio
                                                    )
                                                }}

                                            </span>


                                            <span
                                                class="
                                                    inline-flex
                                                    items-center
                                                    gap-1
                                                    font-semibold
                                                    text-clinica-700
                                                "
                                            >

                                                <Banknote
                                                    :size="13"
                                                />

                                                {{
                                                    formatoPrecio(
                                                        servicioSeleccionado
                                                            .precio_actual
                                                    )
                                                }}

                                            </span>

                                        </div>

                                    </div>


                                    <button
                                        v-if="
                                            !citaConTratamiento
                                            &&
                                            form.tipo_atencion
                                            !==
                                            'CONTINUAR_TRATAMIENTO'
                                        "
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
                                            hover:bg-white
                                            hover:text-rose-600
                                        "
                                        @click="
                                            quitarServicio
                                        "
                                    >

                                        <X :size="17" />

                                    </button>

                                </div>

                            </div>


                            <div
                                v-if="
                                    citaConTratamiento
                                    &&
                                    servicioSeleccionado
                                "
                                class="
                                    mt-2
                                    flex
                                    items-start
                                    gap-2
                                    rounded-xl
                                    bg-violet-50
                                    px-3
                                    py-2.5
                                    text-xs
                                    text-violet-700
                                "
                            >
                                <LockKeyhole
                                    :size="14"
                                    class="mt-0.5 shrink-0"
                                />

                                <span>
                                    Servicio bloqueado: pertenece al tratamiento
                                    seleccionado y no puede modificarse desde esta cita.
                                </span>
                            </div>


                            <p
                                v-else-if="
                                    form.tipo_atencion
                                    ===
                                    'CONTINUAR_TRATAMIENTO'
                                    &&
                                    servicioSeleccionado
                                "
                                class="
                                    mt-2
                                    text-xs
                                    text-violet-600
                                "
                            >
                                Este servicio pertenece al tratamiento seleccionado
                                y no puede cambiarse en esta sesión.
                            </p>


                            <!-- BUSCADOR SERVICIO -->

                            <div
                                v-if="
                                    !servicioSeleccionado
                                    &&
                                    !citaConTratamiento
                                    &&
                                    form.tipo_atencion
                                    !==
                                    'CONTINUAR_TRATAMIENTO'
                                "
                                class="relative"
                            >

                                <Search
                                    :size="18"
                                    class="
                                        absolute
                                        left-3.5
                                        top-3.5
                                        text-slate-400
                                    "
                                />


                                <input
                                    v-model="
                                        textoServicio
                                    "
                                    type="search"
                                    autocomplete="off"
                                    class="
                                        input-clinica
                                        pl-10
                                        pr-10
                                    "
                                    placeholder="Buscar servicio..."
                                >


                                <LoaderCircle
                                    v-if="
                                        buscandoServicio
                                    "
                                    :size="17"
                                    class="
                                        absolute
                                        right-3.5
                                        top-3.5
                                        animate-spin
                                        text-clinica-600
                                    "
                                />


                                <div
                                    v-if="
                                        textoServicio
                                            .trim()
                                            .length >= 2
                                    "
                                    class="
                                        mt-2
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        shadow-lg
                                    "
                                >

                                    <button
                                        v-for="
                                            servicio
                                            in resultadosServicios
                                        "
                                        :key="
                                            servicio.id
                                        "
                                        type="button"
                                        class="
                                            flex
                                            w-full
                                            items-center
                                            justify-between
                                            gap-4
                                            border-b
                                            border-slate-100
                                            px-4
                                            py-3
                                            text-left
                                            transition
                                            last:border-0
                                            hover:bg-slate-50
                                        "
                                        @click="
                                            seleccionarServicio(
                                                servicio
                                            )
                                        "
                                    >

                                        <div>

                                            <p
                                                class="
                                                    text-sm
                                                    font-semibold
                                                    text-slate-800
                                                "
                                            >
                                                {{
                                                    servicio.nombre
                                                }}
                                            </p>


                                            <p
                                                class="
                                                    mt-1
                                                    text-xs
                                                    text-slate-400
                                                "
                                            >
                                                {{
                                                    servicio.codigo
                                                    ??
                                                    'Sin código'
                                                }}
                                            </p>

                                        </div>


                                        <div
                                            class="
                                                shrink-0
                                                text-right
                                            "
                                        >

                                            <p
                                                class="
                                                    text-xs
                                                    text-slate-500
                                                "
                                            >
                                                {{
                                                    servicio
                                                        .duracion_estimada_minutos
                                                }}
                                                min
                                            </p>


                                            <p
                                                class="
                                                    mt-1
                                                    text-xs
                                                    font-semibold
                                                    text-clinica-700
                                                "
                                            >
                                                {{
                                                    formatoPrecio(
                                                        servicio
                                                            .precio_actual
                                                    )
                                                }}
                                            </p>

                                        </div>

                                    </button>


                                    <p
                                        v-if="
                                            !buscandoServicio
                                            &&
                                            resultadosServicios
                                                .length === 0
                                        "
                                        class="
                                            px-4
                                            py-5
                                            text-center
                                            text-sm
                                            text-slate-400
                                        "
                                    >
                                        No encontramos servicios.
                                    </p>

                                </div>

                            </div>


                            <div
                                v-if="
                                    form.tipo_atencion === 'CONTINUAR_TRATAMIENTO'
                                    &&
                                    !servicioSeleccionado
                                "
                                class="
                                    rounded-xl
                                    border
                                    border-dashed
                                    border-amber-200
                                    bg-amber-50/60
                                    px-4
                                    py-3
                                    text-xs
                                    text-amber-700
                                "
                            >
                                Selecciona primero uno de los tratamientos activos.
                                Su servicio se cargará automáticamente.
                            </div>


                            <p
                                v-if="
                                    form.errors
                                        .servicio_id
                                "
                                class="error-text"
                            >
                                {{
                                    form.errors
                                        .servicio_id
                                }}
                            </p>

                        </section>


                        <!-- =========================================================
                            PRECIO ACORDADO
                        ========================================================= -->

                        <div
                            v-if="
                                !props.cita?.id
                                &&
                                form.tipo_atencion
                                ===
                                'NUEVO_TRATAMIENTO'
                                &&
                                form.servicio_id
                            "
                        >

                            <label
                                class="
                                    mb-1.5
                                    block
                                    text-xs
                                    font-bold
                                    text-slate-600
                                "
                            >
                                Precio acordado
                                <span class="text-rose-500">
                                    *
                                </span>
                            </label>


                            <div
                                class="relative"
                            >

                                <span
                                    class="
                                        absolute
                                        left-3
                                        top-1/2
                                        -translate-y-1/2
                                        text-sm
                                        font-bold
                                        text-slate-400
                                    "
                                >
                                    S/
                                </span>


                                <input
                                    v-model="
                                        form.precio_acordado
                                    "
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="
                                        input-clinica
                                        w-full
                                        pl-10
                                    "
                                    placeholder="0.00"
                                >

                            </div>


                            <p
                                v-if="
                                    servicioSeleccionado
                                "
                                class="
                                    mt-1.5
                                    text-xs
                                    text-slate-400
                                "
                            >
                                Precio actual del catálogo:
                                {{
                                    dinero(
                                        servicioSeleccionado
                                            .precio_actual
                                    )
                                }}
                            </p>


                            <p
                                v-if="
                                    form.errors.precio_acordado
                                "
                                class="
                                    mt-1
                                    text-xs
                                    text-rose-600
                                "
                            >
                                {{
                                    form.errors.precio_acordado
                                }}
                            </p>

                        </div>

                        <!-- =============================================
                             PROFESIONAL Y CONSULTORIO
                        ============================================== -->

                        <section
                            class="
                                grid
                                gap-5
                                sm:grid-cols-2
                            "
                        >

                            <!-- PROFESIONAL -->

                            <div>

                                <label
                                    class="
                                        form-label
                                        flex
                                        items-center
                                        gap-2
                                    "
                                >

                                    <Stethoscope
                                        :size="14"
                                    />

                                    Profesional

                                </label>


                                <select
                                    v-model="
                                        form.profesional_id
                                    "
                                    class="input-clinica"
                                >

                                    <option value="">
                                        Selecciona
                                    </option>


                                    <option
                                        v-for="
                                            profesional
                                            in profesionales
                                        "
                                        :key="
                                            profesional.id
                                        "
                                        :value="
                                            profesional.id
                                        "
                                    >
                                        {{
                                            profesional.nombres
                                        }}

                                        {{
                                            profesional.apellidos
                                        }}
                                    </option>

                                </select>


                                <p
                                    v-if="
                                        form.errors
                                            .profesional_id
                                    "
                                    class="error-text"
                                >
                                    {{
                                        form.errors
                                            .profesional_id
                                    }}
                                </p>

                            </div>


                            <!-- CONSULTORIO -->

                            <div>

                                <label
                                    class="
                                        form-label
                                        flex
                                        items-center
                                        gap-2
                                    "
                                >

                                    <DoorOpen
                                        :size="14"
                                    />

                                    Consultorio

                                </label>


                                <select
                                    v-model="
                                        form.consultorio_id
                                    "
                                    class="input-clinica"
                                >

                                    <option value="">
                                        Sin asignar
                                    </option>


                                    <option
                                        v-for="
                                            consultorio
                                            in consultorios
                                        "
                                        :key="
                                            consultorio.id
                                        "
                                        :value="
                                            consultorio.id
                                        "
                                    >
                                        {{
                                            consultorio.nombre
                                        }}
                                    </option>

                                </select>


                                <p
                                    v-if="
                                        form.errors
                                            .consultorio_id
                                    "
                                    class="error-text"
                                >
                                    {{
                                        form.errors
                                            .consultorio_id
                                    }}
                                </p>

                            </div>

                        </section>


                        <!-- =============================================
                             HORARIO
                        ============================================== -->

                        <section
                            class="
                                border-t
                                border-slate-100
                                pt-6
                            "
                        >

                            <div
                                class="
                                    mb-4
                                    flex
                                    items-center
                                    gap-2
                                "
                            >

                                <Timer
                                    :size="17"
                                    class="
                                        text-clinica-700
                                    "
                                />


                                <h3
                                    class="
                                        text-sm
                                        font-bold
                                        text-slate-900
                                    "
                                >
                                    Horario
                                </h3>

                            </div>


                            <div
                                class="
                                    grid
                                    gap-5
                                    sm:grid-cols-2
                                "
                            >

                                <!-- INICIO -->

                                <div>

                                    <label
                                        class="form-label"
                                    >
                                        Inicio
                                    </label>


                                    <input
                                        v-model="
                                            form.fecha_hora_inicio
                                        "
                                        type="datetime-local"
                                        step="60"
                                        class="input-clinica"
                                    >


                                    <p
                                        class="
                                            mt-2
                                            text-[11px]
                                            text-slate-400
                                        "
                                    >
                                        Puedes elegir cualquier minuto.
                                    </p>


                                    <p
                                        v-if="
                                            form.errors
                                                .fecha_hora_inicio
                                        "
                                        class="error-text"
                                    >
                                        {{
                                            form.errors
                                                .fecha_hora_inicio
                                        }}
                                    </p>

                                </div>


                                <!-- FIN -->

                                <div>

                                    <label
                                        class="form-label"
                                    >
                                        Fin estimado
                                    </label>


                                    <div
                                        class="
                                            flex
                                            h-11
                                            items-center
                                            rounded-xl
                                            border
                                            border-slate-200
                                            bg-slate-50
                                            px-4
                                        "
                                    >

                                        <Clock3
                                            :size="16"
                                            class="
                                                mr-2
                                                shrink-0
                                                text-slate-400
                                            "
                                        />


                                        <span
                                            class="
                                                text-sm
                                                font-semibold
                                                text-slate-700
                                            "
                                        >
                                            {{
                                                horaFinal()
                                            }}
                                        </span>


                                        <span
                                            v-if="
                                                duracionServicio
                                            "
                                            class="
                                                ml-2
                                                text-xs
                                                text-slate-400
                                            "
                                        >
                                            {{
                                                formatoDuracion(
                                                    duracionServicio
                                                )
                                            }}
                                        </span>

                                    </div>

                                </div>

                            </div>


                            <!-- =========================================
                                 HORARIOS DISPONIBLES
                            ========================================== -->

                            <div
                                v-if="
                                    form.profesional_id
                                    &&
                                    form.servicio_id
                                    &&
                                    fechaSeleccionada()
                                "
                                class="
                                    mt-5
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-slate-50
                                    p-4
                                "
                            >

                                <div
                                    class="
                                        flex
                                        items-start
                                        justify-between
                                        gap-3
                                    "
                                >

                                    <div>

                                        <p
                                            class="
                                                text-xs
                                                font-bold
                                                text-slate-700
                                            "
                                        >
                                            Horarios disponibles
                                        </p>


                                        <p
                                            class="
                                                mt-1
                                                text-[11px]
                                                leading-5
                                                text-slate-400
                                            "
                                        >
                                            Elige una sugerencia o escribe
                                            una hora exacta manualmente.
                                        </p>

                                    </div>


                                    <LoaderCircle
                                        v-if="
                                            cargandoHorarios
                                        "
                                        :size="18"
                                        class="
                                            shrink-0
                                            animate-spin
                                            text-clinica-600
                                        "
                                    />

                                </div>


                                <!-- ERROR -->

                                <p
                                    v-if="
                                        errorHorarios
                                    "
                                    class="
                                        mt-3
                                        rounded-lg
                                        bg-rose-50
                                        p-3
                                        text-xs
                                        text-rose-600
                                    "
                                >
                                    {{ errorHorarios }}
                                </p>


                                <!-- LISTA -->

                                <div
                                    v-else-if="
                                        !cargandoHorarios
                                        &&
                                        horariosDisponibles.length
                                    "
                                    class="
                                        mt-4
                                        grid
                                        grid-cols-3
                                        gap-2
                                        sm:grid-cols-4
                                    "
                                >

                                    <button
                                        v-for="
                                            horario
                                            in horariosDisponibles
                                        "
                                        :key="
                                            horario.inicio
                                        "
                                        type="button"
                                        class="
                                            rounded-xl
                                            border
                                            border-slate-200
                                            bg-white
                                            px-2
                                            py-2.5
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                            transition
                                            hover:border-clinica-300
                                            hover:bg-clinica-50
                                            hover:text-clinica-700
                                        "
                                        :class="{
                                            'border-clinica-500 bg-clinica-50 text-clinica-700 ring-1 ring-clinica-200':
                                                form.fecha_hora_inicio
                                                ===
                                                horario.inicio
                                        }"
                                        @click="
                                            seleccionarHorario(
                                                horario
                                            )
                                        "
                                    >

                                        <span
                                            class="
                                                block
                                                font-bold
                                            "
                                        >
                                            {{ horario.hora }}
                                        </span>


                                        <span
                                            class="
                                                mt-0.5
                                                block
                                                text-[9px]
                                                font-normal
                                                opacity-60
                                            "
                                        >
                                            hasta
                                            {{ horario.fin }}
                                        </span>

                                    </button>

                                </div>


                                <!-- SIN HORARIOS -->

                                <div
                                    v-else-if="
                                        !cargandoHorarios
                                    "
                                    class="
                                        mt-4
                                        rounded-xl
                                        bg-white
                                        p-4
                                        text-center
                                    "
                                >

                                    <p
                                        class="
                                            text-xs
                                            font-semibold
                                            text-slate-600
                                        "
                                    >
                                        No hay horarios sugeridos disponibles.
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            text-[11px]
                                            leading-5
                                            text-slate-400
                                        "
                                    >
                                        Puedes cambiar el profesional,
                                        consultorio o fecha.
                                    </p>

                                </div>

                            </div>

                        </section>


                        <!-- =============================================
                             ESTADO
                        ============================================== -->

                        <section
                            v-if="editando"
                            class="
                                border-t
                                border-slate-100
                                pt-6
                            "
                        >

                            <label
                                class="form-label"
                            >
                                Estado de la cita
                            </label>


                            <select
                                v-model="
                                    form.estado_cita_id
                                "
                                class="input-clinica"
                            >

                                <option
                                    v-for="
                                        estado
                                        in estadosEditables
                                    "
                                    :key="
                                        estado.id
                                    "
                                    :value="
                                        estado.id
                                    "
                                >
                                    {{ estado.nombre }}
                                </option>

                            </select>


                            <p
                                v-if="
                                    form.errors
                                        .estado_cita_id
                                "
                                class="error-text"
                            >
                                {{
                                    form.errors
                                        .estado_cita_id
                                }}
                            </p>


                            <p
                                class="
                                    mt-2
                                    text-xs
                                    text-slate-400
                                "
                            >
                                Para cancelar, finalizar o marcar
                                una ausencia utiliza las acciones
                                específicas de la cita.
                            </p>

                        </section>


                        <!-- =============================================
                             MOTIVO
                        ============================================== -->

                        <div>

                            <label
                                class="form-label"
                            >
                                Motivo
                            </label>


                            <input
                                v-model="
                                    form.motivo
                                "
                                type="text"
                                class="input-clinica"
                                placeholder="Motivo de la cita..."
                            >


                            <p
                                v-if="
                                    form.errors
                                        .motivo
                                "
                                class="error-text"
                            >
                                {{
                                    form.errors
                                        .motivo
                                }}
                            </p>

                        </div>


                        <!-- =============================================
                             OBSERVACIONES
                        ============================================== -->

                        <div>

                            <label
                                class="form-label"
                            >
                                Observaciones
                            </label>


                            <textarea
                                v-model="
                                    form.observaciones
                                "
                                rows="4"
                                class="
                                    input-clinica
                                    min-h-28
                                    py-3
                                "
                                placeholder="Información adicional..."
                            />


                            <p
                                v-if="
                                    form.errors
                                        .observaciones
                                "
                                class="error-text"
                            >
                                {{
                                    form.errors
                                        .observaciones
                                }}
                            </p>

                        </div>

                    </div>


                    <!-- =================================================
                         FOOTER
                    ================================================== -->

                    <footer
                        class="
                            flex
                            shrink-0
                            justify-end
                            gap-3
                            border-t
                            border-slate-200
                            bg-white
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
                                transition
                                hover:bg-slate-50
                            "
                            @click="cerrar"
                        >
                            Cancelar
                        </button>


                        <button
                            type="submit"
                            :disabled="
                                form.processing
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-clinica-700
                                px-5
                                py-2.5
                                text-sm
                                font-semibold
                                text-white
                                transition
                                hover:bg-clinica-800
                                disabled:cursor-not-allowed
                                disabled:opacity-60
                            "
                        >

                            <LoaderCircle
                                v-if="
                                    form.processing
                                "
                                :size="17"
                                class="
                                    animate-spin
                                "
                            />


                            <Save
                                v-else
                                :size="17"
                            />


                            {{
                                form.processing
                                    ? 'Guardando...'
                                    : editando
                                        ? 'Guardar cambios'
                                        : 'Programar cita'
                            }}

                        </button>

                    </footer>

                </form>

            </aside>

        </Transition>

    </div>

</template>