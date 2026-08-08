<?php

namespace App\Console\Commands;

use App\Services\Finance\DecolectaExchangeRateService;
use Illuminate\Console\Command;
use Throwable;

class FetchExchangeRateCommand extends Command
{
    protected $signature = 'exchange-rates:fetch {--date= : Fecha YYYY-MM-DD (opcional)}';

    protected $description = 'Consulta y guarda el tipo de cambio SUNAT USD/PEN (Decolecta)';

    public function handle(DecolectaExchangeRateService $service): int
    {
        try {
            $rate = $service->fetchAndStore($this->option('date') ?: null);
        } catch (Throwable $e) {
            $this->error('No se pudo obtener el tipo de cambio: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Tipo de cambio %s: compra %s / venta %s (%s→%s)',
            $rate->rate_date?->toDateString(),
            $rate->buy_price,
            $rate->sell_price,
            $rate->base_currency,
            $rate->quote_currency,
        ));

        return self::SUCCESS;
    }
}
