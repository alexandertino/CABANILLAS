<?php

namespace App\Services;

use App\Contracts\AgendaServiceInterface;

class ChatbotService
{
    public function __construct(private readonly AgendaServiceInterface $agenda)
    {
    }

    public function services(): array
    {
        return $this->execute(fn () => $this->agenda->services());
    }

    public function professionals(): array
    {
        return $this->execute(fn () => $this->agenda->professionals());
    }

    public function availability(array $parameters): array
    {
        return $this->execute(fn () => $this->agenda->availability($parameters));
    }

    public function findPatient(string $document): array
    {
        return $this->execute(fn () => $this->agenda->findPatient($document));
    }

    public function createPatient(array $data): array
    {
        return $this->execute(fn () => $this->agenda->createPatient($data));
    }

    public function createAppointment(array $data): array
    {
        return $this->execute(fn () => $this->agenda->createAppointment($data));
    }

    public function appointment(int $id): array
    {
        return $this->execute(fn () => $this->agenda->appointment($id));
    }

    public function confirmAppointment(int $id): array
    {
        return $this->execute(fn () => $this->agenda->confirmAppointment($id));
    }

    public function cancelAppointment(int $id, array $data = []): array
    {
        return $this->execute(fn () => $this->agenda->cancelAppointment($id, $data));
    }

    public function rescheduleAppointment(int $id, array $data): array
    {
        return $this->execute(fn () => $this->agenda->rescheduleAppointment($id, $data));
    }

    private function execute(\Closure $operation): array
    {
        try {
            return ['success' => true, 'data' => $operation()];
        } catch (AgendaApiException $exception) {
            return [
                'success' => false,
                'data' => [],
                'message' => $exception->isConflict()
                    ? 'Ese horario acaba de ser ocupado. Voy a consultar otros horarios disponibles.'
                    : 'No fue posible realizar la operación en este momento.',
            ];
        }
    }
}