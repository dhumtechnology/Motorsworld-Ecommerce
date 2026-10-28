<?php

namespace App\Actions\Orders;

use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Orders\OrderStatus;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Products\ProductStatus;
use App\Models\Auth\User;
use App\Models\Cart\Cart;
use App\Models\Coupons\Coupon;
use App\Models\Coupons\CouponUsage;
use App\Models\Locations\District;
use App\Models\Orders\Address;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Orders\OrderStatusHistory;
use App\Models\Products\Product;
use App\Services\Cart\CartTotalsService;
use App\Services\Checkout\CheckoutChargeAssembler;
use App\Services\Coupons\CouponEvaluator;
use App\Services\Coupons\CouponQuote;
use App\Services\Finance\DecolectaExchangeRateService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderFromCartAction
{
    public function __construct(
        private readonly DecolectaExchangeRateService $exchangeRates,
        private readonly CartTotalsService $cartTotals,
        private readonly CouponEvaluator $couponEvaluator,
        private readonly CheckoutChargeAssembler $checkoutCharges,
    ) {}

    /**
     * Congela precios del carrito y crea el pedido.
     * Por defecto vacía el carrito; en checkout con pasarela usar clearCart=false
     * y vaciarlo solo si el pago queda aprobado o en confirmación.
     *
     * @throws ValidationException
     */
    public function execute(
        User $user,
        Cart $cart,
        ?Address $shippingAddress = null,
        ?Address $billingAddress = null,
        ?FulfillmentMethod $fulfillmentMethod = null,
        bool $clearCart = true,
        ?Coupon $coupon = null,
        ?PaymentMethod $paymentMethod = null,
    ): Order {
        $fulfillmentMethod ??= FulfillmentMethod::Delivery;

        $cart->loadMissing([
            'items.product.activeOffer',
            'items.product.category',
            'items.product.vehicleModel:id,brand_id',
            'items.variant.inventory',
            'items.variant.product',
        ]);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Tu carrito está vacío.',
            ]);
        }

        $exchangeSnapshot = $this->exchangeRates->snapshotForOrder();

        return DB::transaction(function () use ($user, $cart, $shippingAddress, $billingAddress, $fulfillmentMethod, $exchangeSnapshot, $clearCart, $coupon, $paymentMethod) {
            $lines = [];
            $evalLines = collect();

            foreach ($cart->items as $item) {
                $product = $item->product;
                $variant = $item->variant;

                if ($product === null || $product->status !== ProductStatus::Active || $variant === null || ! $variant->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => "El producto «{$item->product_id}» ya no está disponible.",
                    ]);
                }

                $available = (int) ($variant->inventory?->available_stock ?? 0);

                if ($item->quantity > $available) {
                    throw ValidationException::withMessages([
                        'cart' => "Stock insuficiente para «{$product->name}» ({$variant->colorLabel()}). Disponible: {$available}.",
                    ]);
                }

                $pricing = OrderItem::pricingAttributesFor($product, $item->quantity, null, $variant);
                $lines[] = $pricing;
                $evalLines->push([
                    'product' => $product,
                    'quantity' => $item->quantity,
                    'line_total' => (float) $pricing['unit_price'] * (int) $pricing['quantity'],
                    'currency' => $pricing['currency'] ?? 'PEN',
                ]);
            }

            $totals = $this->cartTotals->summarize(
                collect($lines)->map(fn (array $line) => [
                    'line_total' => (float) $line['unit_price'] * (int) $line['quantity'],
                    'currency' => $line['currency'] ?? 'PEN',
                ]),
                isset($exchangeSnapshot['sell']) ? (float) $exchangeSnapshot['sell'] : null,
                $exchangeSnapshot['date'] ?? null,
            );

            $shippingAddress?->loadMissing('district');
            $district = $shippingAddress?->district;
            $quote = null;

            if ($coupon !== null) {
                $quote = $this->applyCoupon(
                    $coupon,
                    $user,
                    $evalLines,
                    $totals,
                    $fulfillmentMethod,
                    $district,
                    $paymentMethod,
                );
            }

            $charge = $this->checkoutCharges->assemble($totals, $quote, $fulfillmentMethod, $district);
            $subtotal = $charge->subtotal;
            $discount = $charge->discount;
            $shipping = $charge->shipping;
            $total = $charge->total;
            $currency = $charge->currency;

            if ($total < 1) {
                throw ValidationException::withMessages([
                    'cart' => 'El monto mínimo de compra es S/ 1.00.',
                ]);
            }

            if ($fulfillmentMethod === FulfillmentMethod::Delivery && $shippingAddress === null) {
                throw ValidationException::withMessages([
                    'address_line1' => 'La dirección es obligatoria para delivery.',
                ]);
            }

            $order = Order::query()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Created,
                'payment_status' => PaymentStatus::Pending,
                'subtotal_amount' => round($subtotal, 2),
                'discount_amount' => round($discount, 2),
                'shipping_amount' => round($shipping, 2),
                'shipping_rate_id' => $charge->shippingQuote->rate?->id,
                'total_amount' => round($total, 2),
                'currency' => $currency,
                'coupon_id' => $quote?->coupon?->id,
                'coupon_code' => $quote?->coupon?->code,
                'exchange_rate_buy' => $exchangeSnapshot['buy'] ?? null,
                'exchange_rate_sell' => $exchangeSnapshot['sell'] ?? null,
                'exchange_rate_date' => $exchangeSnapshot['date'] ?? null,
                'fulfillment_method' => $fulfillmentMethod,
                'shipping_address_id' => $fulfillmentMethod === FulfillmentMethod::Pickup
                    ? null
                    : $shippingAddress?->id,
                'billing_address_id' => $fulfillmentMethod === FulfillmentMethod::Pickup
                    ? null
                    : ($billingAddress?->id ?? $shippingAddress?->id),
            ]);

            foreach ($lines as $line) {
                OrderItem::query()->create([
                    ...$line,
                    'order_id' => $order->id,
                    'created_at' => now(),
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => OrderStatus::Created->value,
                'note' => 'Pedido creado desde checkout',
                'created_at' => now(),
            ]);

            if ($quote !== null) {
                $this->recordCouponUsage($quote, $user, $order);
            }

            if ($clearCart) {
                $cart->items()->delete();
            }

            return $order->load(['items.product', 'items.variant', 'user.customerProfile']);
        });
    }

    /**
     * @param  Collection<int, array{product: Product, quantity: int, line_total: float, currency: string}>  $lines
     */
    private function applyCoupon(
        Coupon $coupon,
        User $user,
        $lines,
        $totals,
        FulfillmentMethod $fulfillmentMethod,
        ?District $district,
        ?PaymentMethod $paymentMethod,
    ): CouponQuote {
        $locked = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->first();

        if ($locked === null) {
            throw ValidationException::withMessages([
                'coupon_code' => 'El cupón ya no está disponible.',
            ]);
        }

        return $this->couponEvaluator->quote(
            $locked,
            $lines,
            $totals,
            $user,
            $fulfillmentMethod,
            $district,
            $paymentMethod,
        );
    }

    private function recordCouponUsage(CouponQuote $quote, User $user, Order $order): void
    {
        try {
            CouponUsage::query()->create([
                'coupon_id' => $quote->coupon->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'discount_amount' => $quote->discountAmount,
                'currency' => $quote->currency,
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Ya usaste este cupón en una compra anterior.',
            ]);
        }
    }
}
