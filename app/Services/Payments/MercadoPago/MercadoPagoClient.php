<?php

namespace App\Services\Payments\MercadoPago;

use App\Services\Payments\MercadoPago\Exceptions\MercadoPagoApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MercadoPagoClient
{
    private const API_BASE = 'https://api.mercadopago.com';

    public function __construct(
        private readonly ?string $accessToken = null,
        private readonly ?string $publicKey = null,
        private readonly bool $fake = false,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            accessToken: config('services.mercadopago.access_token'),
            publicKey: config('services.mercadopago.public_key'),
            fake: (bool) config('services.mercadopago.fake', false),
        );
    }

    public function isFake(): bool
    {
        return $this->fake;
    }

    public function publicKey(): string
    {
        if ($this->fake) {
            return 'TEST-fake-motoworld-public-key';
        }

        $key = $this->publicKey ?? config('services.mercadopago.public_key');

        if (! is_string($key) || trim($key) === '') {
            throw MercadoPagoApiException::configuration('Falta MERCADOPAGO_PUBLIC_KEY.');
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPayment(array $payload, ?string $idempotencyKey = null): array
    {
        if ($this->fake) {
            return $this->fakePayment($payload);
        }

        return $this->request(
            'POST',
            '/v1/payments',
            $payload,
            $idempotencyKey ?? (string) Str::uuid(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentId): array
    {
        if ($this->fake) {
            return [
                'id' => $paymentId,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'fake' => true,
            ];
        }

        return $this->request('GET', '/v1/payments/'.$paymentId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function fakePayment(array $payload): array
    {
        $token = (string) ($payload['token'] ?? '');

        if (str_contains(Str::lower($token), 'fail')) {
            throw MercadoPagoApiException::fromApi(400, 'Tu pago fue rechazado (modo fake).', [
                'status' => 'rejected',
                'status_detail' => 'cc_rejected_other_reason',
            ]);
        }

        $id = random_int(1000000000, 1999999999);

        return [
            'id' => $id,
            'status' => 'approved',
            'status_detail' => 'accredited',
            'transaction_amount' => $payload['transaction_amount'] ?? 0,
            'payment_method_id' => $payload['payment_method_id'] ?? 'visa',
            'payment_type_id' => ($payload['payment_method_id'] ?? '') === 'yape' ? 'bank_transfer' : 'credit_card',
            'date_approved' => now()->toIso8601String(),
            'external_reference' => $payload['external_reference'] ?? null,
            'fake' => true,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $payload = null, ?string $idempotencyKey = null): array
    {
        $token = $this->accessToken ?? config('services.mercadopago.access_token');

        if (! is_string($token) || trim($token) === '') {
            throw MercadoPagoApiException::configuration('Falta MERCADOPAGO_ACCESS_TOKEN.');
        }

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($idempotencyKey !== null) {
            $headers['X-Idempotency-Key'] = $idempotencyKey;
        }

        $pending = Http::withHeaders($headers)->timeout(30);

        $response = match (Str::upper($method)) {
            'GET' => $pending->get(self::API_BASE.$path),
            default => $pending->post(self::API_BASE.$path, $payload ?? []),
        };

        $body = $response->json();
        $data = is_array($body) ? $body : [];

        if ($response->failed()) {
            $message = $data['message']
                ?? $data['error']
                ?? (is_string($body) ? $body : 'Error en la API de Mercado Pago.');

            if (isset($data['cause'][0]['description']) && is_string($data['cause'][0]['description'])) {
                $message = $data['cause'][0]['description'];
            }

            throw MercadoPagoApiException::fromApi($response->status(), (string) $message, $data);
        }

        return $data;
    }
}
