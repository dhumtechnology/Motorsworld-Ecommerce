<?php

namespace App\Services\Checkout;

use App\Enums\Orders\FulfillmentMethod;
use App\Models\Locations\District;
use App\Services\Cart\CartCurrencyTotals;
use App\Services\Coupons\CouponQuote;
use App\Services\Shipping\ShippingRateResolver;
use App\Support\Currency;

class CheckoutChargeAssembler
{
    public function __construct(
        private readonly ShippingRateResolver $shippingRates,
    ) {}

    public function assemble(
        CartCurrencyTotals $totals,
        ?CouponQuote $coupon,
        ?FulfillmentMethod $fulfillment,
        ?District $district,
    ): CheckoutCharge {
        $currency = $coupon?->currency ?? $totals->chargeCurrency();
        $subtotal = $coupon?->subtotalAmount ?? $totals->chargeAmount();
        $discount = $coupon?->discountAmount ?? 0.0;

        $shipping = $this->shippingRates->quote(
            $fulfillment,
            $district,
            $currency,
            $totals->sellRate,
        );

        $total = round(max(0, $subtotal - $discount + $shipping->amount), 2);
        $grandPen = round(max(0, ($coupon?->grandPen ?? $totals->grandPen) + $shipping->amountPen), 2);
        $grandUsd = round(max(0, ($coupon?->grandUsd ?? $totals->grandUsd) + $shipping->amountUsd), 2);

        return new CheckoutCharge(
            subtotal: round($subtotal, 2),
            discount: round($discount, 2),
            shipping: $shipping->amount,
            total: $total,
            currency: Currency::normalize($currency),
            grandPen: $grandPen,
            grandUsd: $grandUsd,
            coupon: $coupon,
            shippingQuote: $shipping,
        );
    }
}
