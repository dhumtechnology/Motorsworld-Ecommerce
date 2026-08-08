<?php

namespace App\Services\Finance;

use App\Models\Finance\ExchangeRate;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DecolectaExchangeRateService
{
    /**
     * Consulta el tipo de cambio SUNAT (USD → PEN) y lo persiste.
     *
     * @throws RuntimeException|RequestException
     */
    public function fetchAndStore(?string $date = null): ExchangeRate
    {
        $token = (string) config('services.decolecta.token');

        if ($token === '') {
            throw new RuntimeException('Falta configurar DECOLECTA_API_TOKEN.');
        }

        $baseUrl = rtrim((string) config('services.decolecta.base_url'), '/');
        $url = $baseUrl.'/tipo-cambio/sunat';

        $query = [];
        if ($date !== null && $date !== '') {
            $query['date'] = $date;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->withHeaders([
                'Referer' => (string) config('services.decolecta.referer'),
            ])
            ->get($url, $query)
            ->throw();

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        $buy = (float) ($payload['buy_price'] ?? 0);
        $sell = (float) ($payload['sell_price'] ?? 0);
        $rateDate = (string) ($payload['date'] ?? $date ?? now('America/Lima')->toDateString());
        $base = strtoupper((string) ($payload['base_currency'] ?? 'USD'));
        $quote = strtoupper((string) ($payload['quote_currency'] ?? 'PEN'));

        if ($buy <= 0 || $sell <= 0) {
            Log::warning('Decolecta exchange rate response invalid', ['payload' => $payload]);
            throw new RuntimeException('La API de tipo de cambio devolvió valores inválidos.');
        }

        return ExchangeRate::query()->updateOrCreate(
            [
                'rate_date' => $rateDate,
                'base_currency' => $base,
                'quote_currency' => $quote,
            ],
            [
                'buy_price' => $buy,
                'sell_price' => $sell,
                'source' => 'decolecta',
                'payload' => $payload,
                'fetched_at' => now(),
            ],
        );
    }

    public function current(): ?ExchangeRate
    {
        return ExchangeRate::latestAvailable();
    }

    /**
     * Snapshot para congelar en un pedido.
     *
     * @return array{buy: float, sell: float, date: string}|null
     */
    public function snapshotForOrder(): ?array
    {
        $rate = $this->current();

        if ($rate === null) {
            try {
                $rate = $this->fetchAndStore();
            } catch (\Throwable $e) {
                Log::warning('No se pudo obtener tipo de cambio al crear pedido', [
                    'message' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return [
            'buy' => (float) $rate->buy_price,
            'sell' => (float) $rate->sell_price,
            'date' => $rate->rate_date?->toDateString() ?? now('America/Lima')->toDateString(),
        ];
    }
}
