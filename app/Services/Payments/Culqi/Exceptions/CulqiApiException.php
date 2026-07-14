<?php

namespace App\Services\Payments\Culqi\Exceptions;

use Exception;

class CulqiApiException extends Exception
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly array $payload = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    public static function configuration(string $message): self
    {
        return new self($message, 500);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromApi(int $status, string $message, array $payload = []): self
    {
        return new self($message, $status, $payload);
    }
}
