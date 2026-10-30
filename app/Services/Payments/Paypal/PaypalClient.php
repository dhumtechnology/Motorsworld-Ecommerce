<?php

namespace App\Services\Payments\Paypal;

use App\Services\Payments\Paypal\Exceptions\PaypalApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaypalClient
{
    public function __construct(
        private readonly ?string $clientId = null,
        private readonly ?string $secret = null,
        private readonly string $mode = 'sandbox',
        private readonly bool $fake = false,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            clientId: config('services.paypal.client_id'),
            secret: config('services.paypal.secret'),
            mode: (string) config('services.paypal.mode', 'sandbox'),
            fake: (bool) config('services.paypal.fake', false),
        );
    }

    public function isFake(): bool
    {
        return $this->fake;
    }

    public function clientId(): string
    {
        if ($this->fake) {
            return 'sb-fake-motoworld';
        }

        $id = $this->clientId ?? config('services.paypal.client_id');

        if (! is_string($id) || $id === '') {
            throw PaypalApiException::configuration('PAYPAL_CLIENT_ID no está configurado.');
        }

        return $id;
    }

    public function mode(): string
    {
        return $this->mode === 'live' ? 'live' : 'sandbox';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOrder(array $payload): array
    {
        if ($this->fake) {
            return $this->fakeCreatedOrder($payload);
        }

        return $this->request('POST', '/v2/checkout/orders', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function captureOrder(string $paypalOrderId): array
    {
        if ($this->fake) {
            return $this->fakeCapturedOrder($paypalOrderId);
        }

        return $this->request('POST', '/v2/checkout/orders/'.$paypalOrderId.'/capture', null);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $paypalOrderId): array
    {
        if ($this->fake) {
            return $this->fakeCreatedOrder(['id' => $paypalOrderId]);
        }

        return $this->request('GET', '/v2/checkout/orders/'.$paypalOrderId);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $payload = [], bool $retried = false): array
    {
        $http = $this->http();
        $response = match (strtoupper($method)) {
            'GET' => $http->get($this->baseUrl().$path),
            default => $payload === null
                ? $http->withBody('{}', 'application/json')->post($this->baseUrl().$path)
                : $http->post($this->baseUrl().$path, $payload),
        };

        if ($response->status() === 401 && ! $retried) {
            Cache::forget($this->tokenCacheKey());

            return $this->request($method, $path, $payload, true);
        }

        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if ($response->failed()) {
            throw PaypalApiException::fromApi(
                $response->status(),
                $this->errorMessage($body),
                $body,
            );
        }

        return $body;
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function accessToken(): string
    {
        $cached = Cache::get($this->tokenCacheKey());

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::withBasicAuth($this->clientId(), $this->secret())
            ->asForm()
            ->acceptJson()
            ->timeout(20)
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if ($response->failed() || ! is_string($body['access_token'] ?? null) || $body['access_token'] === '') {
            throw PaypalApiException::fromApi(
                $response->status(),
                $this->errorMessage($body) !== 'PayPal rechazó la operación.'
                    ? $this->errorMessage($body)
                    : 'No se pudo autenticar con PayPal. Revisa CLIENT_ID y SECRET.',
                $body,
            );
        }

        $ttl = max(60, ((int) ($body['expires_in'] ?? 300)) - 60);
        Cache::put($this->tokenCacheKey(), $body['access_token'], $ttl);

        return $body['access_token'];
    }

    private function secret(): string
    {
        $secret = $this->secret ?? config('services.paypal.secret');

        if (! is_string($secret) || $secret === '') {
            throw PaypalApiException::configuration('PAYPAL_SECRET no está configurado.');
        }

        return $secret;
    }

    private function baseUrl(): string
    {
        return $this->mode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function tokenCacheKey(): string
    {
        return 'paypal.access_token.'.$this->mode().'.'.md5($this->clientId());
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function errorMessage(array $body): string
    {
        $details = $body['details'][0]['description'] ?? null;

        if (is_string($details) && trim($details) !== '') {
            return trim($details);
        }

        foreach (['message', 'error_description', 'error'] as $key) {
            $value = $body[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return 'PayPal rechazó la operación.';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function fakeCreatedOrder(array $payload): array
    {
        $amount = $payload['purchase_units'][0]['amount'] ?? ['currency_code' => 'PEN', 'value' => '1.00'];

        return [
            'id' => is_string($payload['id'] ?? null) && $payload['id'] !== ''
                ? $payload['id']
                : 'PAYPAL_FAKE_'.Str::upper(Str::random(12)),
            'status' => 'CREATED',
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) ($payload['purchase_units'][0]['reference_id'] ?? 'fake'),
                'custom_id' => (string) ($payload['purchase_units'][0]['custom_id'] ?? ''),
                'amount' => $amount,
            ]],
            'fake' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fakeCapturedOrder(string $paypalOrderId): array
    {
        return [
            'id' => $paypalOrderId,
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'payments' => [
                    'captures' => [[
                        'id' => 'PAYPAL_CAPTURE_FAKE_'.Str::upper(Str::random(10)),
                        'status' => 'COMPLETED',
                        'amount' => [
                            'currency_code' => 'PEN',
                            'value' => '0.00',
                        ],
                    ]],
                ],
            ]],
            'fake' => true,
        ];
    }
}
