<?php

namespace App\Services\Shipping;

use App\Enums\Orders\FulfillmentMethod;
use App\Models\Locations\District;
use App\Models\Shipping\ShippingRate;
use App\Support\Currency;

class ShippingRateResolver
{
    public function quote(
        ?FulfillmentMethod $fulfillment,
        ?District $district,
        string $chargeCurrency,
        ?float $sellRate,
    ): ShippingQuote {
        if ($fulfillment !== FulfillmentMethod::Delivery || $district === null) {
            return $this->empty($chargeCurrency);
        }

        $rate = $this->matchingRate($district);

        if ($rate === null) {
            return $this->empty($chargeCurrency);
        }

        $from = Currency::normalize($rate->currency);
        $to = Currency::normalize($chargeCurrency);
        $amount = Currency::convert((float) $rate->price, $from, $to, $sellRate);
        $amountPen = Currency::convert((float) $rate->price, $from, 'PEN', $sellRate);
        $amountUsd = Currency::convert((float) $rate->price, $from, 'USD', $sellRate);

        return new ShippingQuote(
            amount: round($amount, 2),
            currency: $to,
            amountPen: round($amountPen, 2),
            amountUsd: round($amountUsd, 2),
            rate: $rate,
        );
    }

    public function matchingRate(?District $district): ?ShippingRate
    {
        if ($district !== null) {
            $direct = ShippingRate::query()
                ->where('is_active', true)
                ->whereHas('districts', fn ($query) => $query->where('districts.id', $district->id))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if ($direct !== null) {
                return $direct;
            }
        }

        return ShippingRate::query()
            ->where('is_active', true)
            ->where('is_fallback', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    private function empty(string $currency): ShippingQuote
    {
        $currency = Currency::normalize($currency);

        return new ShippingQuote(
            amount: 0.0,
            currency: $currency,
            amountPen: 0.0,
            amountUsd: 0.0,
            rate: null,
        );
    }
}
