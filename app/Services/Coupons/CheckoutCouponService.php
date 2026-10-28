<?php

namespace App\Services\Coupons;

use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Payments\PaymentMethod;
use App\Models\Auth\User;
use App\Models\Cart\Cart;
use App\Models\Coupons\Coupon;
use App\Models\Products\Product;
use App\Services\Cart\CartCurrencyTotals;
use App\Services\Cart\CartTotalsService;
use App\Services\Orders\ProductPricingService;
use App\Support\Currency;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CheckoutCouponService
{
    public const SESSION_KEY = 'checkout.coupon_code';

    public function __construct(
        private readonly ProductPricingService $pricing,
        private readonly CartTotalsService $cartTotals,
        private readonly CouponEvaluator $evaluator,
    ) {}

    /**
     * @return Collection<int, array{product: Product, variant: mixed, quantity: int, unit_price: float, list_unit_price: float, line_total: float, is_on_sale: bool, currency: string, color_label: ?string}>
     */
    public function cartLines(Cart $cart): Collection
    {
        $cart->loadMissing([
            'items.product.category',
            'items.product.primaryImage',
            'items.product.activeOffer',
            'items.product.vehicleModel:id,brand_id',
            'items.variant.inventory',
            'items.variant.images',
            'items.variant.colors',
        ]);

        return $cart->items
            ->filter(fn ($item) => $item->product !== null)
            ->map(function ($item) {
                $product = $item->product;
                $pricing = $this->pricing->resolve($product);

                return [
                    'product' => $product,
                    'variant' => $item->variant,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $pricing->unitPrice,
                    'list_unit_price' => (float) $pricing->listUnitPrice,
                    'line_total' => (float) $pricing->unitPrice * $item->quantity,
                    'is_on_sale' => $pricing->hasOffer(),
                    'currency' => $pricing->currency,
                    'color_label' => $item->variant?->colorLabel(),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array{product: Product, quantity: int, line_total: float, currency: string}>  $lines
     *
     * @throws ValidationException
     */
    public function quote(
        string $code,
        Collection $lines,
        CartCurrencyTotals $totals,
        ?User $user,
        ?FulfillmentMethod $fulfillment = null,
        ?string $city = null,
        ?PaymentMethod $paymentMethod = null,
        bool $enforceCheckoutRules = true,
    ): CouponQuote {
        $coupon = $this->evaluator->findByCode($code);

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'coupon_code' => 'El cupón no existe.',
            ]);
        }

        return $this->evaluator->quote(
            $coupon,
            $lines,
            $totals,
            $user,
            $fulfillment,
            $city,
            $paymentMethod,
            enforceAudience: true,
            enforceCheckoutRules: $enforceCheckoutRules,
        );
    }

    /**
     * @param  Collection<int, array{product: Product, quantity: int, line_total: float, currency: string}>  $lines
     */
    public function quoteOrNull(
        ?string $code,
        Collection $lines,
        CartCurrencyTotals $totals,
        ?User $user,
        ?FulfillmentMethod $fulfillment = null,
        ?string $city = null,
        ?PaymentMethod $paymentMethod = null,
    ): ?CouponQuote {
        if ($code === null || trim($code) === '') {
            return null;
        }

        try {
            return $this->quote($code, $lines, $totals, $user, $fulfillment, $city, $paymentMethod, false);
        } catch (ValidationException) {
            return null;
        }
    }

    public function findByCode(string $code): ?Coupon
    {
        return $this->evaluator->findByCode($code);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(CouponQuote $quote): array
    {
        $coupon = $quote->coupon;

        return [
            'valid' => true,
            'code' => $coupon->code,
            'name' => $coupon->displayTitle(),
            'type' => $coupon->type->value,
            'value_label' => $coupon->discountLabel(),
            'applied_label' => $quote->appliedLabel(),
            'conversion_note' => $quote->conversionNote(),
            'discount_amount' => $quote->discountAmount,
            'discount_formatted' => Currency::format($quote->discountAmount, $quote->currency),
            'discount_pen' => $quote->discountPen,
            'discount_usd' => $quote->discountUsd,
            'subtotal_amount' => $quote->subtotalAmount,
            'total' => $quote->chargeAmount,
            'amount_cents' => (int) round($quote->chargeAmount * 100),
            'currency' => $quote->currency,
            'grand_pen' => $quote->grandPen,
            'grand_usd' => $quote->grandUsd,
            'message' => 'Cupón '.$coupon->code.' aplicado · '.$coupon->discountLabel().'.',
        ];
    }
}
