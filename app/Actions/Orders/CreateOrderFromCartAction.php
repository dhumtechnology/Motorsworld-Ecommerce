<?php

namespace App\Actions\Orders;

use App\Enums\Orders\OrderStatus;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Products\ProductStatus;
use App\Models\Auth\User;
use App\Models\Cart\Cart;
use App\Models\Orders\Address;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Orders\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderFromCartAction
{
    /**
     * Congela precios del carrito, crea el pedido y vacía el carrito.
     *
     * @throws ValidationException
     */
    public function execute(
        User $user,
        Cart $cart,
        ?Address $shippingAddress = null,
        ?Address $billingAddress = null,
    ): Order {
        $cart->loadMissing(['items.product.inventory', 'items.product.activeOffer']);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Tu carrito está vacío.',
            ]);
        }

        return DB::transaction(function () use ($user, $cart, $shippingAddress, $billingAddress) {
            $lines = [];
            $total = 0.0;
            $currency = 'PEN';

            foreach ($cart->items as $item) {
                $product = $item->product;

                if ($product === null || $product->status !== ProductStatus::Active) {
                    throw ValidationException::withMessages([
                        'cart' => "El producto «{$item->product_id}» ya no está disponible.",
                    ]);
                }

                $available = (int) ($product->inventory?->available_stock ?? 0);

                if ($item->quantity > $available) {
                    throw ValidationException::withMessages([
                        'cart' => "Stock insuficiente para «{$product->name}». Disponible: {$available}.",
                    ]);
                }

                $pricing = OrderItem::pricingAttributesFor($product, $item->quantity);
                $lineTotal = (float) $pricing['unit_price'] * $pricing['quantity'];
                $total += $lineTotal;
                $currency = $pricing['currency'];
                $lines[] = $pricing;
            }

            if ($total < 1) {
                throw ValidationException::withMessages([
                    'cart' => 'El monto mínimo de compra es S/ 1.00.',
                ]);
            }

            $order = Order::query()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Created,
                'payment_status' => PaymentStatus::Pending,
                'total_amount' => round($total, 2),
                'currency' => $currency,
                'shipping_address_id' => $shippingAddress?->id,
                'billing_address_id' => $billingAddress?->id ?? $shippingAddress?->id,
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

            $cart->items()->delete();

            return $order->load(['items.product', 'user.customerProfile']);
        });
    }
}
