<?php

namespace App\Services;

use App\Contracts\AgendaServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApiAgendaService implements AgendaServiceInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function services(): array
    {
        return $this->request('get', 'api/servicios')->json('data', []);
    }

    public function professionals(): array
    {
        return $this->request('get', 'api/profesionales')->json('data', []);
    }

    public function availability(array $parameters): array
    {
        return $this->request('get', 'api/disponibilidad', $parameters)->json('data', []);
    }

    public function findPatient(string $document): array
    {
        return $this->request('get', 'api/pacientes', ['numero_documento' => $document])->json('data', []);
    }

    public function createPatient(array $data): array
    {
        return $this->request('post', 'api/pacientes', $data)->json('data', []);
    }

    public function createAppointment(array $data): array
    {
        return $this->request('post', 'api/citas', $data)->json('data', []);
    }

    public function appointment(int $id): array
    {
        return $this->request('get', "api/citas/{$id}")->json('data', []);
    }

    public function confirmAppointment(int $id): array
    {
        return $this->request('post', "api/citas/{$id}/confirmar")->json('data', []);
    }

    public function cancelAppointment(int $id, array $data = []): array
    {
        return $this->request('post', "api/citas/{$id}/cancelar", $data)->json('data', []);
    }

    public function rescheduleAppointment(int $id, array $data): array
    {
        return $this->request('post', "api/citas/{$id}/reprogramar", $data)->json('data', []);
    }

    private function request(string $method, string $uri, array $data = []): Response
    {
        try {
            $request = $this->http();
            $response = $method === 'get'
                ? $request->get($uri, $data)
                : $request->{$method}($uri, $data);

            $response->throw();

            return $response;
        } catch (ConnectionException|RequestException $exception) {
            $response = $exception instanceof RequestException ? $exception->response : null;

            throw new AgendaApiException(
                $response?->json('message') ?? 'No fue posible comunicarse con la Agenda API.',
                $response?->status(),
                $response?->json() ?? [],
                $exception,
            );
        }
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->acceptJson()
            ->timeout($this->timeout);
    }
}