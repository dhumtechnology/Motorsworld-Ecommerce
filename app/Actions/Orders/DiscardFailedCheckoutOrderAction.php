<?php

namespace App\Actions\Orders;

use App\Enums\Orders\PaymentStatus;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;

class DiscardFailedCheckoutOrderAction
{
    /**
     * Elimina un pedido de checkout cuyo pago falló (y direcciones huérfanas asociadas).
     */
    public function execute(Order $order): void
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing(['payments', 'items', 'statusHistory', 'shippingAddress', 'billingAddress']);

            $shippingId = $order->shipping_address_id;
            $billingId = $order->billing_address_id;

            $order->payments()->delete();
            $order->items()->delete();
            $order->statusHistory()->delete();
            $order->delete();

            foreach (array_unique(array_filter([$shippingId, $billingId])) as $addressId) {
                $stillUsed = Order::query()
                    ->where(function ($q) use ($addressId) {
                        $q->where('shipping_address_id', $addressId)
                            ->orWhere('billing_address_id', $addressId);
                    })
                    ->exists();

                if (! $stillUsed) {
                    \App\Models\Orders\Address::query()->whereKey($addressId)->delete();
                }
            }
        });
    }
}
