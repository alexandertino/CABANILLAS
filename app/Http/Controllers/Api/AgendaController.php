<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Clinica\CitaController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelApiCitaRequest;
use App\Http\Requests\Api\RescheduleApiCitaRequest;
use App\Http\Requests\Api\StoreApiCitaRequest;
use App\Http\Requests\Api\StoreApiPacienteRequest;
use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\HistorialEstadoCita;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgendaController extends Controller
{
    public function patient(Request $request): JsonResponse
    {
        $document = trim((string) $request->query('numero_documento', ''));

        if ($document === '') {
            return response()->json([
                'message' => 'El parámetro numero_documento es obligatorio.',
                'errors' => ['numero_documento' => ['El parámetro es obligatorio.']],
            ], 422);
        }

        $patient = Paciente::query()
            ->where('numero_documento', $document)
            ->where('activo', true)
            ->first();

        if (! $patient) {
            return response()->json(['message' => 'Paciente no encontrado.'], 404);
        }

        return response()->json(['data' => $this->patientData($patient)]);
    }

    public function createPatient(StoreApiPacienteRequest $request): JsonResponse
    {
        $patient = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['codigo'] = 'TMP-' . Str::ulid();
            $patient = Paciente::create($data);
            $patient->update(['codigo' => 'PAC-' . str_pad((string) $patient->id, 6, '0', STR_PAD_LEFT)]);

            return $patient->refresh();
        });

        return response()->json(['data' => $this->patientData($patient)], 201);
    }

    public function services(): JsonResponse
    {
        $services = Servicio::query()->where('activo', true)->orderBy('nombre')->get([
            'id', 'codigo', 'nombre', 'descripcion', 'duracion_estimada_minutos', 'precio_actual',
        ]);

        return response()->json(['data' => $services]);
    }

    public function professionals(): JsonResponse
    {
        $professionals = Profesional::query()->where('activo', true)->orderBy('apellidos')->orderBy('nombres')->get([
            'id', 'nombres', 'apellidos', 'numero_documento', 'numero_colegiatura', 'telefono', 'correo',
        ]);

        return response()->json(['data' => $professionals]);
    }

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'servicio_id' => ['required', 'integer', 'exists:servicios,id'],
            'profesional_id' => ['required', 'integer', 'exists:profesionales,id'],
            'fecha' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'consultorio_id' => ['nullable', 'integer', 'exists:consultorios,id'],
        ]);

        $service = app(CitaController::class)->obtenerServicio((int) $data['servicio_id']);
        $date = Carbon::parse($data['fecha'])->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        $occupied = Cita::query()
            ->with(['estadoCita:id,codigo,nombre'])
            ->where(function ($query) use ($data) {
                $query->where('profesional_id', $data['profesional_id']);

                if (! empty($data['consultorio_id'])) {
                    $query->orWhere('consultorio_id', $data['consultorio_id']);
                }
            })
            ->where('fecha_hora_inicio', '<', $endOfDay)
            ->where(function ($query) use ($date) {
                $query->whereIn('estado_cita_id', EstadoCita::query()->whereIn('codigo', ['PENDIENTE', 'CONFIRMADA', 'EN_ATENCION'])->pluck('id'))
                    ->where('fecha_hora_fin', '>', $date)
                    ->orWhere(function ($query) use ($date) {
                        $query->whereHas('estadoCita', fn ($status) => $status->where('codigo', 'ATENDIDA'))
                            ->whereRaw('COALESCE(fecha_hora_fin_real, fecha_hora_fin) > ?', [$date->format('Y-m-d H:i:s')]);
                    });
            })
            ->orderBy('fecha_hora_inicio')
            ->get(['id', 'fecha_hora_inicio', 'fecha_hora_fin', 'fecha_hora_fin_real', 'estado_cita_id']);

        $available = null;
        $requested = null;

        if (! empty($data['hora'])) {
            $requested = Carbon::parse($date->format('Y-m-d') . ' ' . $data['hora']);
            $requestedEnd = $requested->copy()->addMinutes((int) $service->duracion_estimada_minutos);
            $payload = [
                'profesional_id' => $data['profesional_id'],
                'consultorio_id' => $data['consultorio_id'] ?? null,
                'fecha_hora_inicio' => $requested,
                'fecha_hora_fin' => $requestedEnd,
            ];

            try {
                app(CitaController::class)->validarDisponibilidad($payload);
                $available = true;
            } catch (ValidationException) {
                $available = false;
            }
        }

        return response()->json([
            'data' => [
                'fecha' => $date->toDateString(),
                'profesional_id' => (int) $data['profesional_id'],
                'servicio_id' => (int) $data['servicio_id'],
                'duracion_minutos' => (int) $service->duracion_estimada_minutos,
                'available' => $available,
                'requested' => $requested ? [
                    'fecha_hora_inicio' => $requested->toISOString(),
                    'fecha_hora_fin' => $requested->copy()->addMinutes((int) $service->duracion_estimada_minutos)->toISOString(),
                ] : null,
                'occupied' => $occupied->map(fn (Cita $appointment) => [
                    'id' => $appointment->id,
                    'fecha_hora_inicio' => $appointment->fecha_hora_inicio->toISOString(),
                    'fecha_hora_fin' => ($appointment->fecha_hora_fin_real ?? $appointment->fecha_hora_fin)->toISOString(),
                    'estado' => $appointment->estadoCita?->codigo,
                ])->values(),
            ],
        ]);
    }

    public function createAppointment(StoreApiCitaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $service = app(CitaController::class)->obtenerServicio((int) $data['servicio_id']);
        $start = Carbon::parse($data['fecha_hora_inicio']);
        $end = $start->copy()->addMinutes((int) $service->duracion_estimada_minutos);
        $pending = EstadoCita::query()->where('codigo', 'PENDIENTE')->first();

        if (! $pending) {
            return response()->json(['message' => 'No está configurado el estado PENDIENTE.'], 500);
        }

        try {
            $appointment = DB::transaction(function () use ($data, $service, $start, $end, $pending) {
                app(CitaController::class)->validarDisponibilidad([
                    'profesional_id' => $data['profesional_id'],
                    'consultorio_id' => $data['consultorio_id'] ?? null,
                    'fecha_hora_inicio' => $start,
                    'fecha_hora_fin' => $end,
                ]);

                $appointment = Cita::create([
                    'paciente_id' => $data['paciente_id'],
                    'profesional_id' => $data['profesional_id'],
                    'consultorio_id' => $data['consultorio_id'] ?? null,
                    'estado_cita_id' => $pending->id,
                    'fecha_hora_inicio' => $start,
                    'fecha_hora_fin' => $end,
                    'motivo' => $data['motivo'] ?? null,
                    'observaciones' => $data['observaciones'] ?? null,
                    'usuario_creador_id' => null,
                ]);

                app(CitaController::class)->guardarServicioCita($appointment, $service);
                HistorialEstadoCita::create([
                    'cita_id' => $appointment->id,
                    'estado_anterior_id' => null,
                    'estado_nuevo_id' => $pending->id,
                    'usuario_id' => null,
                    'motivo' => 'Creación inicial de la cita mediante API',
                    'fecha_cambio' => now(),
                ]);

                return $appointment;
            });
        } catch (ValidationException $exception) {
            return $this->conflictOrValidation($exception);
        }

        return response()->json(['data' => $this->appointmentData($appointment->refresh())], 201);
    }

    public function appointment(Cita $cita): JsonResponse
    {
        return response()->json(['data' => $this->appointmentData($cita)]);
    }

    public function patientAppointments(Paciente $paciente): JsonResponse
    {
        $appointments = $paciente->citas()
            ->with(['paciente', 'profesional', 'estadoCita', 'consultorio', 'serviciosCita.servicio'])
            ->orderBy('fecha_hora_inicio')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $appointments->map(fn (Cita $appointment) => $this->appointmentData($appointment))->values(),
        ]);
    }

    public function confirmAppointment(Cita $cita): JsonResponse
    {
        $confirmed = EstadoCita::query()->where('codigo', 'CONFIRMADA')->first();

        if (! $confirmed) {
            return response()->json(['message' => 'No está configurado el estado CONFIRMADA.'], 500);
        }

        if ($cita->estado_cita_id === $confirmed->id) {
            return response()->json(['data' => $this->appointmentData($cita)]);
        }

        try {
            app(CitaController::class)->validarDisponibilidad([
                'profesional_id' => $cita->profesional_id,
                'consultorio_id' => $cita->consultorio_id,
                'fecha_hora_inicio' => $cita->fecha_hora_inicio,
                'fecha_hora_fin' => $cita->fecha_hora_fin,
            ], $cita);
        } catch (ValidationException $exception) {
            return $this->conflictOrValidation($exception);
        }

        $appointment = DB::transaction(function () use ($cita, $confirmed) {
            $oldState = $cita->estado_cita_id;
            $cita->update(['estado_cita_id' => $confirmed->id, 'fecha_hora_fin_real' => null, 'fecha_cancelacion' => null, 'motivo_cancelacion' => null]);
            HistorialEstadoCita::create([
                'cita_id' => $cita->id, 'estado_anterior_id' => $oldState, 'estado_nuevo_id' => $confirmed->id,
                'usuario_id' => null, 'motivo' => 'Cita confirmada mediante API', 'fecha_cambio' => now(),
            ]);
            return $cita->refresh();
        });

        return response()->json(['data' => $this->appointmentData($appointment)]);
    }

    public function cancelAppointment(CancelApiCitaRequest $request, Cita $cita): JsonResponse
    {
        $cancelled = EstadoCita::query()->where('codigo', 'CANCELADA')->first();

        if (! $cancelled) {
            return response()->json(['message' => 'No está configurado el estado CANCELADA.'], 500);
        }

        if ($cita->estado_cita_id === $cancelled->id) {
            return response()->json(['data' => $this->appointmentData($cita)]);
        }

        $data = $request->validated();
        $appointment = DB::transaction(function () use ($cita, $cancelled, $data) {
            $oldState = $cita->estado_cita_id;
            $cita->update(['estado_cita_id' => $cancelled->id, 'fecha_cancelacion' => now(), 'motivo_cancelacion' => $data['motivo_cancelacion'] ?? null]);
            HistorialEstadoCita::create([
                'cita_id' => $cita->id, 'estado_anterior_id' => $oldState, 'estado_nuevo_id' => $cancelled->id,
                'usuario_id' => null, 'motivo' => $data['motivo_cancelacion'] ?? 'Cita cancelada mediante API', 'fecha_cambio' => now(),
            ]);
            return $cita->refresh();
        });

        return response()->json(['data' => $this->appointmentData($appointment)]);
    }

    public function rescheduleAppointment(RescheduleApiCitaRequest $request, Cita $cita): JsonResponse
    {
        $cancelled = EstadoCita::query()->where('codigo', 'CANCELADA')->first();

        if (! $cancelled) {
            return response()->json(['message' => 'No está configurado el estado CANCELADA.'], 500);
        }

        if ($cita->estado_cita_id === $cancelled->id) {
            return response()->json(['message' => 'Una cita cancelada no puede reprogramarse.'], 422);
        }

        $service = $cita->serviciosCita()->with('servicio')->first()?->servicio;

        if (! $service) {
            return response()->json(['message' => 'La cita no tiene un servicio asociado.'], 422);
        }

        $start = Carbon::parse($request->validated()['fecha_hora_inicio']);
        $end = $start->copy()->addMinutes((int) $service->duracion_estimada_minutos);

        try {
            app(CitaController::class)->validarDisponibilidad([
                'profesional_id' => $cita->profesional_id, 'consultorio_id' => $cita->consultorio_id,
                'fecha_hora_inicio' => $start, 'fecha_hora_fin' => $end,
            ], $cita);
        } catch (ValidationException $exception) {
            return $this->conflictOrValidation($exception);
        }

        $cita->update(['fecha_hora_inicio' => $start, 'fecha_hora_fin' => $end]);

        return response()->json(['data' => $this->appointmentData($cita->refresh())]);
    }

    private function patientData(Paciente $patient): array
    {
        return $patient->only(['id', 'tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'fecha_nacimiento', 'telefono', 'correo', 'direccion', 'observaciones']);
    }

    private function appointmentData(Cita $appointment): array
    {
        $appointment->loadMissing(['paciente', 'profesional', 'estadoCita', 'consultorio', 'serviciosCita.servicio']);
        $service = $appointment->serviciosCita->first()?->servicio;

        return [
            'id' => $appointment->id,
            'paciente' => $appointment->paciente?->only(['id', 'tipo_documento', 'numero_documento', 'nombres', 'apellidos']),
            'servicio' => $service?->only(['id', 'codigo', 'nombre', 'duracion_estimada_minutos', 'precio_actual']),
            'profesional' => $appointment->profesional?->only(['id', 'nombres', 'apellidos', 'numero_colegiatura']),
            'consultorio' => $appointment->consultorio?->only(['id', 'codigo', 'nombre']),
            'fecha' => $appointment->fecha_hora_inicio?->toDateString(),
            'hora' => $appointment->fecha_hora_inicio?->format('H:i:s'),
            'fecha_hora_inicio' => $appointment->fecha_hora_inicio?->toISOString(),
            'fecha_hora_fin' => $appointment->fecha_hora_fin?->toISOString(),
            'estado' => $appointment->estadoCita?->only(['id', 'codigo', 'nombre', 'es_final']),
            'motivo' => $appointment->motivo,
            'observaciones' => $appointment->observaciones,
        ];
    }

    private function conflictOrValidation(ValidationException $exception): JsonResponse
    {
        $messages = $exception->errors();
        $text = strtolower(json_encode($messages));

        return response()->json([
            'message' => str_contains($text, 'horario') || str_contains($text, 'ocupado')
                ? 'El horario solicitado no está disponible.'
                : 'Los datos enviados no son válidos.',
            'errors' => $messages,
        ], str_contains($text, 'horario') || str_contains($text, 'ocupado') ? 409 : 422);
    }
}
