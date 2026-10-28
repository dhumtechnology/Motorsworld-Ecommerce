<?php

namespace App\Services\Checkout;

use App\Services\Coupons\CouponQuote;
use App\Services\Shipping\ShippingQuote;
use App\Support\Currency;

final class CheckoutCharge
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $shipping,
        public readonly float $total,
        public readonly string $currency,
        public readonly float $grandPen,
        public readonly float $grandUsd,
        public readonly ?CouponQuote $coupon,
        public readonly ShippingQuote $shippingQuote,
    ) {}

    public function amountCents(): int
    {
        return (int) round($this->total * 100);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $shipping = $this->shippingQuote;

        return [
            'subtotal_amount' => $this->subtotal,
            'discount_amount' => $this->discount,
            'shipping_amount' => $this->shipping,
            'shipping_formatted' => Currency::format($this->shipping, $this->currency),
            'shipping_label' => $shipping->label(),
            'shipping_pen' => $shipping->amountPen,
            'shipping_usd' => $shipping->amountUsd,
            'has_shipping' => $this->shipping > 0,
            'total' => $this->total,
            'amount_cents' => $this->amountCents(),
            'currency' => $this->currency,
            'grand_pen' => $this->grandPen,
            'grand_usd' => $this->grandUsd,
        ];
    }
}
