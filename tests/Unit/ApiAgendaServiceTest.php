<?php

namespace Tests\Unit;

use App\Services\AgendaApiException;
use App\Services\ApiAgendaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiAgendaServiceTest extends TestCase
{
    private function service(): ApiAgendaService
    {
        return new ApiAgendaService('http://agenda.test', 'test-key', 7);
    }

    public function test_reads_services_and_sends_api_key(): void
    {
        Http::fake(['agenda.test/*' => Http::response(['data' => [['id' => 1, 'nombre' => 'Limpieza']]])]);

        $services = $this->service()->services();

        $this->assertSame([['id' => 1, 'nombre' => 'Limpieza']], $services);
        Http::assertSent(fn ($request) => $request->url() === 'http://agenda.test/api/servicios'
            && $request->header('X-API-Key')[0] === 'test-key');
    }

    public function test_reads_professionals_and_availability_with_query_parameters(): void
    {
        Http::fake([
            'agenda.test/api/profesionales' => Http::response(['data' => [['id' => 3]]]),
            'agenda.test/api/disponibilidad*' => Http::response(['data' => ['available' => true]]),
        ]);

        $this->assertSame([['id' => 3]], $this->service()->professionals());
        $this->assertSame(
            ['available' => true],
            $this->service()->availability(['servicio_id' => 1, 'profesional_id' => 3, 'fecha' => '2026-09-10'])
        );

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api/disponibilidad')
            && str_contains($request->url(), 'profesional_id=3'));
    }

    public function test_reads_and_creates_patients(): void
    {
        Http::fake([
            'agenda.test/api/pacientes?numero_documento=123' => Http::response(['data' => ['id' => 5]]),
            'agenda.test/api/pacientes' => Http::response(['data' => ['id' => 6]], 201),
        ]);

        $this->assertSame(['id' => 5], $this->service()->findPatient('123'));
        $this->assertSame(['id' => 6], $this->service()->createPatient(['numero_documento' => '456']));

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'http://agenda.test/api/pacientes'
            && $request['numero_documento'] === '456');
    }

    public function test_manages_appointments(): void
    {
        Http::fake([
            'agenda.test/api/citas/10' => Http::response(['data' => ['id' => 10]]),
            'agenda.test/api/citas' => Http::response(['data' => ['id' => 11]], 201),
            'agenda.test/api/citas/10/confirmar' => Http::response(['data' => ['id' => 10, 'estado' => 'CONFIRMADA']]),
            'agenda.test/api/citas/10/cancelar' => Http::response(['data' => ['id' => 10, 'estado' => 'CANCELADA']]),
            'agenda.test/api/citas/10/reprogramar' => Http::response(['data' => ['id' => 10, 'fecha' => '2026-09-11']]),
        ]);

        $service = $this->service();

        $this->assertSame(['id' => 11], $service->createAppointment(['paciente_id' => 1]));
        $this->assertSame(['id' => 10], $service->appointment(10));
        $this->assertSame(['id' => 10, 'estado' => 'CONFIRMADA'], $service->confirmAppointment(10));
        $this->assertSame(['id' => 10, 'estado' => 'CANCELADA'], $service->cancelAppointment(10, ['motivo_cancelacion' => 'Viaje']));
        $this->assertSame(['id' => 10, 'fecha' => '2026-09-11'], $service->rescheduleAppointment(10, ['fecha_hora_inicio' => '2026-09-11 10:00']));
    }

    public function test_converts_http_errors_to_agenda_exception(): void
    {
        Http::fake(['agenda.test/*' => Http::response(['message' => 'El horario solicitado no está disponible.'], 409)]);

        try {
            $this->service()->confirmAppointment(10);
            $this->fail('Expected AgendaApiException was not thrown.');
        } catch (AgendaApiException $exception) {
            $this->assertSame(409, $exception->status);
            $this->assertTrue($exception->isConflict());
            $this->assertSame('El horario solicitado no está disponible.', $exception->response['message']);
        }
    }
}