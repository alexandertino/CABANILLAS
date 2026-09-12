<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\ServicioCita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.clinic_api.key' => 'test-key']);
    }

    public function test_returns_a_patients_appointments_with_the_expected_data(): void
    {
        $patient = $this->createPatient();
        $appointment = $this->createAppointment($patient, '2026-09-10 16:00:00');

        $response = $this->withHeader('X-API-Key', 'test-key')
            ->getJson("/api/pacientes/{$patient->id}/citas");

        $response->assertOk()
            ->assertJsonPath('data.0.id', $appointment->id)
            ->assertJsonPath('data.0.paciente.id', $patient->id)
            ->assertJsonPath('data.0.servicio.nombre', 'Limpieza dental')
            ->assertJsonPath('data.0.profesional.id', $appointment->profesional_id)
            ->assertJsonPath('data.0.fecha', '2026-09-10')
            ->assertJsonPath('data.0.hora', '16:00:00')
            ->assertJsonPath('data.0.estado.codigo', 'PENDIENTE');
    }

    public function test_returns_an_empty_list_for_a_patient_without_appointments(): void
    {
        $patient = $this->createPatient();

        $response = $this->withHeader('X-API-Key', 'test-key')
            ->getJson("/api/pacientes/{$patient->id}/citas");

        $response->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_returns_not_found_for_a_missing_patient(): void
    {
        $this->withHeader('X-API-Key', 'test-key')
            ->getJson('/api/pacientes/999999/citas')
            ->assertNotFound();
    }

    public function test_only_returns_appointments_belonging_to_the_requested_patient(): void
    {
        $patientA = $this->createPatient();
        $patientB = $this->createPatient();
        $appointmentA = $this->createAppointment($patientA, '2026-09-10 10:00:00');
        $appointmentB = $this->createAppointment($patientB, '2026-09-10 11:00:00');

        $response = $this->withHeader('X-API-Key', 'test-key')
            ->getJson("/api/pacientes/{$patientA->id}/citas");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $appointmentA->id)
            ->assertJsonMissing(['id' => $appointmentB->id]);
    }

    public function test_rejects_requests_without_the_api_key(): void
    {
        $patient = $this->createPatient();

        $this->getJson("/api/pacientes/{$patient->id}/citas")
            ->assertUnauthorized();
    }

    public function test_returns_appointments_in_start_time_order_with_id_tiebreaker(): void
    {
        $patient = $this->createPatient();
        $later = $this->createAppointment($patient, '2026-09-12 09:00:00');
        $earlier = $this->createAppointment($patient, '2026-09-10 09:00:00');
        $sameTimeLowerId = $this->createAppointment($patient, '2026-09-11 09:00:00');
        $sameTimeHigherId = $this->createAppointment($patient, '2026-09-11 09:00:00');

        $response = $this->withHeader('X-API-Key', 'test-key')
            ->getJson("/api/pacientes/{$patient->id}/citas");

        $response->assertOk()->assertJsonPath('data.0.id', $earlier->id)
            ->assertJsonPath('data.1.id', $sameTimeLowerId->id)
            ->assertJsonPath('data.2.id', $sameTimeHigherId->id)
            ->assertJsonPath('data.3.id', $later->id);
    }

    public function test_preserves_the_state_of_each_appointment(): void
    {
        $patient = $this->createPatient();
        $pending = EstadoCita::where('codigo', 'PENDIENTE')->firstOrFail();
        $cancelled = EstadoCita::where('codigo', 'CANCELADA')->firstOrFail();
        $pendingAppointment = $this->createAppointment($patient, '2026-09-10 09:00:00', $pending);
        $cancelledAppointment = $this->createAppointment($patient, '2026-09-11 09:00:00', $cancelled);

        $response = $this->withHeader('X-API-Key', 'test-key')
            ->getJson("/api/pacientes/{$patient->id}/citas");

        $response->assertOk()
            ->assertJsonPath('data.0.id', $pendingAppointment->id)
            ->assertJsonPath('data.0.estado.codigo', 'PENDIENTE')
            ->assertJsonPath('data.1.id', $cancelledAppointment->id)
            ->assertJsonPath('data.1.estado.codigo', 'CANCELADA');
    }

    private function createPatient(): Paciente
    {
        $number = (string) (10000000 + Paciente::count());

        return Paciente::create([
            'codigo' => 'PAC-' . uniqid(),
            'tipo_documento' => 'DNI',
            'numero_documento' => $number,
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'activo' => true,
        ]);
    }

    private function createAppointment(Paciente $patient, string $start, ?EstadoCita $status = null): Cita
    {
        $professional = Profesional::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Ramirez',
            'numero_documento' => 'DOC-' . uniqid(),
            'numero_colegiatura' => 'COL-' . uniqid(),
            'activo' => true,
        ]);
        $service = Servicio::create([
            'codigo' => 'LIMP-' . uniqid(),
            'nombre' => 'Limpieza dental',
            'duracion_estimada_minutos' => 60,
            'precio_actual' => 100,
            'activo' => true,
        ]);
        $status ??= EstadoCita::where('codigo', 'PENDIENTE')->firstOrFail();
        $appointment = Cita::create([
            'paciente_id' => $patient->id,
            'profesional_id' => $professional->id,
            'estado_cita_id' => $status->id,
            'fecha_hora_inicio' => $start,
            'fecha_hora_fin' => date('Y-m-d H:i:s', strtotime($start . ' +1 hour')),
        ]);

        ServicioCita::create([
            'cita_id' => $appointment->id,
            'servicio_id' => $service->id,
            'precio_unitario' => 100,
            'total' => 100,
        ]);

        return $appointment;
    }
}
