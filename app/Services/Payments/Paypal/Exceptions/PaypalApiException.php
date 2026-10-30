<?php

namespace App\Services\Payments\Paypal\Exceptions;

use Exception;

class PaypalApiException extends Exception
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
        return new self($message !== '' ? $message : 'PayPal rechazó la operación.', $status, $payload);
    }
}
