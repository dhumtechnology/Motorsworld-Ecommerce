<?php

namespace App\Services\Coupons;

use App\Enums\Coupons\CouponType;
use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Payments\PaymentMethod;
use App\Models\Auth\User;
use App\Models\Coupons\Coupon;
use App\Models\Products\Product;
use App\Services\Cart\CartCurrencyTotals;
use App\Services\Cart\CartTotalsService;
use App\Support\Currency;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CouponEvaluator
{
    public function __construct(
        private readonly CartTotalsService $cartTotals,
    ) {}

    /**
     * @param  Collection<int, array{product: Product, quantity: int, line_total: float, currency: string}>  $lines
     *
     * @throws ValidationException
     */
    public function quote(
        Coupon $coupon,
        Collection $lines,
        CartCurrencyTotals $totals,
        ?User $user,
        ?FulfillmentMethod $fulfillment = null,
        ?string $city = null,
        ?PaymentMethod $paymentMethod = null,
        bool $enforceAudience = true,
        bool $enforceCheckoutRules = true,
    ): CouponQuote {
        $coupon->loadMissing(['customers:id', 'products:id', 'categories:id', 'brands:id', 'usages']);

        $this->assertUsable($coupon, $user, $enforceAudience);

        if ($enforceCheckoutRules && $paymentMethod !== null && ! $coupon->allowsPaymentMethod($paymentMethod)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Este cupón no aplica al método de pago seleccionado.',
            ]);
        }

        if ($enforceCheckoutRules
            && $fulfillment === FulfillmentMethod::Delivery
            && $coupon->allowedCities() !== []
            && ! $coupon->allowsCity($city)
        ) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Este cupón no aplica a la zona de entrega indicada.',
            ]);
        }

        $eligible = $this->eligibleLines($coupon, $lines);

        if ($eligible->isEmpty()) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Este cupón no aplica a los productos de tu carrito.',
            ]);
        }

        $eligibleQuantity = (int) $eligible->sum(fn (array $line) => (int) $line['quantity']);

        if ($coupon->min_products !== null && $eligibleQuantity < (int) $coupon->min_products) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Este cupón requiere al menos '.$coupon->min_products.' producto(s) aplicable(s).',
            ]);
        }

        $eligibleTotals = $this->cartTotals->summarize(
            $eligible,
            $totals->sellRate,
            $totals->rateDate,
        );

        if ($coupon->min_subtotal !== null) {
            $minCurrency = Currency::normalize($coupon->min_subtotal_currency ?: 'PEN');
            $eligibleAmount = $minCurrency === 'USD' ? $eligibleTotals->grandUsd : $eligibleTotals->grandPen;

            if ($eligibleAmount + 0.0001 < (float) $coupon->min_subtotal) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'Este cupón requiere un mínimo de '.Currency::format((float) $coupon->min_subtotal, $minCurrency).' en productos aplicables.',
                ]);
            }
        }

        $chargeCurrency = $totals->chargeCurrency();
        $subtotalAmount = $totals->chargeAmount();
        $eligibleCharge = $eligibleTotals->chargeCurrency() === $chargeCurrency
            ? $eligibleTotals->chargeAmount()
            : Currency::convert(
                $eligibleTotals->chargeAmount(),
                $eligibleTotals->chargeCurrency(),
                $chargeCurrency,
                $totals->sellRate,
            );

        $discount = $this->discountAmount($coupon, $eligibleCharge, $chargeCurrency, $totals->sellRate);
        $maxDiscount = max(0.0, round($subtotalAmount - 1, 2));
        $discount = min($discount, $maxDiscount, $eligibleCharge);
        $discount = round(max(0.0, $discount), 2);

        if ($discount <= 0) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Este cupón no genera un descuento válido para esta compra.',
            ]);
        }

        $chargeAmount = round($subtotalAmount - $discount, 2);
        $discountPen = Currency::convert($discount, $chargeCurrency, 'PEN', $totals->sellRate);
        $discountUsd = Currency::convert($discount, $chargeCurrency, 'USD', $totals->sellRate);

        return new CouponQuote(
            coupon: $coupon,
            discountAmount: $discount,
            currency: $chargeCurrency,
            chargeAmount: $chargeAmount,
            subtotalAmount: $subtotalAmount,
            grandPen: round(max(0, $totals->grandPen - $discountPen), 2),
            grandUsd: round(max(0, $totals->grandUsd - $discountUsd), 2),
            eligibleQuantity: $eligibleQuantity,
            discountPen: round($discountPen, 2),
            discountUsd: round($discountUsd, 2),
            sellRate: $totals->sellRate,
        );
    }

    public function findByCode(string $code): ?Coupon
    {
        $normalized = $this->normalizeCode($code);

        if ($normalized === '') {
            return null;
        }

        return Coupon::query()->where('code', $normalized)->first();
    }

    public function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($code)) ?? '');
    }

    private function assertUsable(Coupon $coupon, ?User $user, bool $enforceAudience): void
    {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Este cupón no está disponible.',
            ]);
        }

        $now = now();

        if ($coupon->starts_at->gt($now)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Este cupón todavía no entra en vigencia.',
            ]);
        }

        if ($coupon->ends_at->lt($now)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Este cupón ya expiró.',
            ]);
        }

        if ($enforceAudience && ! $coupon->isPublic()) {
            if ($user === null) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'Este cupón es exclusivo para clientes asignados. Inicia sesión para usarlo.',
                ]);
            }

            $assignedIds = $coupon->customers->pluck('id')->map(fn ($id) => (int) $id)->all();

            if (! in_array((int) $user->id, $assignedIds, true)) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'Este cupón no está asignado a tu cuenta.',
                ]);
            }
        }

        if ($user !== null && $coupon->wasUsedBy((int) $user->id)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Ya usaste este cupón en una compra anterior.',
            ]);
        }
    }

    /**
     * @param  Collection<int, array{product: Product, quantity: int, line_total: float, currency: string}>  $lines
     * @return Collection<int, array{product: Product, quantity: int, line_total: float, currency: string}>
     */
    private function eligibleLines(Coupon $coupon, Collection $lines): Collection
    {
        if (! $coupon->hasCatalogRestriction()) {
            return $lines->values();
        }

        $productIds = $coupon->products->pluck('id')->map(fn ($id) => (int) $id)->all();
        $categoryIds = $coupon->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
        $brandIds = $coupon->brands->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $lines
            ->filter(function (array $line) use ($productIds, $categoryIds, $brandIds) {
                $product = $line['product'];

                if (in_array((int) $product->id, $productIds, true)) {
                    return true;
                }

                if ($product->category_id !== null && in_array((int) $product->category_id, $categoryIds, true)) {
                    return true;
                }

                $brandId = $product->vehicleModel?->brand_id;

                return $brandId !== null && in_array((int) $brandId, $brandIds, true);
            })
            ->values();
    }

    private function discountAmount(
        Coupon $coupon,
        float $eligibleCharge,
        string $chargeCurrency,
        ?float $sellRate,
    ): float {
        if ($coupon->type === CouponType::Percent) {
            return round($eligibleCharge * ((float) $coupon->value / 100), 2);
        }

        $from = Currency::normalize($coupon->currency ?: 'PEN');

        if ($from !== Currency::normalize($chargeCurrency) && ($sellRate === null || $sellRate <= 0)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'No hay tipo de cambio disponible para aplicar este cupón.',
            ]);
        }

        $amount = Currency::convert(
            (float) $coupon->value,
            $from,
            $chargeCurrency,
            $sellRate,
        );

        return round(min($amount, $eligibleCharge), 2);
    }
}
