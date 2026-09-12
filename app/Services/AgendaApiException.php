<?php

namespace App\Services;

use RuntimeException;

class AgendaApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly array $response = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    public function isConflict(): bool
    {
        return $this->status === 409;
    }
}