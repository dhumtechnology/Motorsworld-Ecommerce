<?php

namespace App\Services\Payments\MercadoPago\Exceptions;

use Exception;

class MercadoPagoApiException extends Exception
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $payload = [],
    ) {
        parent::__construct($message, $status);
    }

    public static function configuration(string $message): self
    {
        return new self($message, 0, []);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromApi(int $status, string $message, array $payload = []): self
    {
        return new self($message, $status, $payload);
    }
}
