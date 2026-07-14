<?php

namespace App\Services\Payments\Culqi;

use App\Services\Payments\Culqi\Exceptions\CulqiApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CulqiClient
{
    private const API_BASE = 'https://api.culqi.com/v2';

    public function __construct(
        private readonly ?string $secretKey = null,
        private readonly ?string $publicKey = null,
        private readonly bool $fake = false,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            secretKey: config('services.culqi.secret_key'),
            publicKey: config('services.culqi.public_key'),
            fake: (bool) config('services.culqi.fake', false),
        );
    }

    public function isFake(): bool
    {
        return $this->fake;
    }

    public function publicKey(): string
    {
        if ($this->fake) {
            return 'pk_test_fake_motosworld';
        }

        $key = $this->publicKey ?? config('services.culqi.public_key');

        if (! is_string($key) || $key === '') {
            throw CulqiApiException::configuration('CULQI_PUBLIC_KEY no está configurada.');
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createCharge(array $payload): array
    {
        if ($this->fake) {
            return $this->fakeCharge($payload);
        }

        return $this->post('/charges', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOrder(array $payload): array
    {
        if ($this->fake) {
            return $this->fakeOrder($payload);
        }

        return $this->post('/orders', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId): array
    {
        if ($this->fake) {
            return [
                'object' => 'order',
                'id' => $orderId,
                'state' => 'pending',
            ];
        }

        return $this->get("/orders/{$orderId}");
    }

    /**
     * @return array<string, mixed>
     */
    public function getCharge(string $chargeId): array
    {
        if ($this->fake) {
            return [
                'object' => 'charge',
                'id' => $chargeId,
                'outcome' => ['type' => 'venta_exitosa'],
            ];
        }

        return $this->get("/charges/{$chargeId}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function fakeCharge(array $payload): array
    {
        $sourceId = (string) ($payload['source_id'] ?? '');

        if (str_contains($sourceId, 'fail')) {
            throw CulqiApiException::fromApi(400, 'Cargo de prueba rechazado (token *fail*).', [
                'object' => 'error',
                'type' => 'card_error',
                'merchant_message' => 'Cargo de prueba rechazado (token *fail*).',
                'user_message' => 'Tu tarjeta fue rechazada (modo fake).',
            ]);
        }

        $id = 'chr_test_fake_'.Str::lower(Str::random(16));

        return [
            'object' => 'charge',
            'id' => $id,
            'amount' => $payload['amount'] ?? 0,
            'currency_code' => $payload['currency_code'] ?? 'PEN',
            'email' => $payload['email'] ?? null,
            'source_id' => $sourceId,
            'outcome' => [
                'type' => 'venta_exitosa',
                'code' => 'successful_charge',
                'merchant_message' => 'Cargo fake autorizado (CULQI_FAKE=true).',
                'user_message' => 'Su compra ha sido exitosa (modo prueba local).',
            ],
            'reference_code' => Str::lower(Str::random(10)),
            'metadata' => $payload['metadata'] ?? [],
            'fake' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function fakeOrder(array $payload): array
    {
        $id = 'ord_test_fake_'.Str::lower(Str::random(16));
        $code = (string) random_int(10000000, 99999999);
        $expiration = (int) ($payload['expiration_date'] ?? now()->addDay()->timestamp);

        return [
            'object' => 'order',
            'id' => $id,
            'amount' => $payload['amount'] ?? 0,
            'currency_code' => $payload['currency_code'] ?? 'PEN',
            'description' => $payload['description'] ?? null,
            'order_number' => $payload['order_number'] ?? null,
            'state' => 'pending',
            'payment_code' => $code,
            'qr' => 'https://placehold.co/220x220/png?text=QR+FAKE+'.$code,
            'url_pe' => 'https://example.com/pagoefectivo-fake/'.$code,
            'expiration_date' => $expiration,
            'metadata' => $payload['metadata'] ?? [],
            'fake' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $response = $this->http()->post(self::API_BASE.$path, $payload);

        return $this->parseResponse($response->status(), $response->json(), $path);
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path): array
    {
        $response = $this->http()->get(self::API_BASE.$path);

        return $this->parseResponse($response->status(), $response->json(), $path);
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->secretKey())
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function secretKey(): string
    {
        $key = $this->secretKey ?? config('services.culqi.secret_key');

        if (! is_string($key) || $key === '') {
            throw CulqiApiException::configuration('CULQI_SECRET_KEY no está configurada.');
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    private function parseResponse(int $status, ?array $body, string $path): array
    {
        $body ??= [];

        if ($status >= 200 && $status < 300) {
            return $body;
        }

        $merchantMessage = $body['merchant_message']
            ?? $body['user_message']
            ?? $body['message']
            ?? 'Error en la API de Culqi.';

        Log::warning('Culqi API error', [
            'path' => $path,
            'status' => $status,
            'body' => $body,
        ]);

        throw CulqiApiException::fromApi($status, (string) $merchantMessage, $body);
    }
}
