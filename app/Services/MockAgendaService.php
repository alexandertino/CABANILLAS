<?php

namespace App\Services;

use App\Contracts\AgendaServiceInterface;

class MockAgendaService implements AgendaServiceInterface
{
    public function services(): array { return []; }

    public function professionals(): array { return []; }

    public function availability(array $parameters): array { return []; }

    public function findPatient(string $document): array { return []; }

    public function createPatient(array $data): array { return $data; }

    public function createAppointment(array $data): array { return $data; }

    public function appointment(int $id): array { return ['id' => $id]; }

    public function confirmAppointment(int $id): array { return ['id' => $id]; }

    public function cancelAppointment(int $id, array $data = []): array { return ['id' => $id]; }

    public function rescheduleAppointment(int $id, array $data): array { return ['id' => $id] + $data; }
}