<?php

namespace App\Contracts;

interface AgendaServiceInterface
{
    public function services(): array;

    public function professionals(): array;

    public function availability(array $parameters): array;

    public function findPatient(string $document): array;

    public function createPatient(array $data): array;

    public function createAppointment(array $data): array;

    public function appointment(int $id): array;

    public function confirmAppointment(int $id): array;

    public function cancelAppointment(int $id, array $data = []): array;

    public function rescheduleAppointment(int $id, array $data): array;
}