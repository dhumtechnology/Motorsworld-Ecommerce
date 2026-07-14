<?php

namespace App\Actions\Orders;

use App\Enums\Orders\OrderStatus;
use App\Enums\Orders\PaymentStatus;
use App\Models\Orders\Order;
use App\Models\Orders\OrderStatusHistory;
use App\Models\Orders\Payment;
use App\Enums\Payments\PaymentRecordStatus;
use App\Models\Products\Inventory;
use Illuminate\Support\Facades\DB;

class MarkOrderAsPaidAction
{
    public function execute(Order $order, ?Payment $payment = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $payment, $note) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->payment_status === PaymentStatus::Paid) {
                return $order;
            }

            $order->update([
                'status' => OrderStatus::Paid,
                'payment_status' => PaymentStatus::Paid,
            ]);

            if ($payment !== null && ! $payment->isPaid()) {
                $payment->update([
                    'status' => PaymentRecordStatus::Paid,
                    'paid_at' => now(),
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => OrderStatus::Paid->value,
                'note' => $note ?? 'Pago confirmado',
                'created_at' => now(),
            ]);

            $this->deductInventory($order);

            return $order->fresh(['items', 'payments']);
        });
    }

    private function deductInventory(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $inventory = Inventory::query()
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($inventory === null) {
                continue;
            }

            $qty = (int) $item->quantity;
            $inventory->available_stock = max(0, (int) $inventory->available_stock - $qty);
            $inventory->total_stock = max(0, (int) $inventory->total_stock - $qty);
            $inventory->save();
        }
    }
}
