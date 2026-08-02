<?php

namespace App\Actions\Admin\Orders;

use App\Enums\Orders\OrderStatus;
use App\Models\Orders\Order;
use App\Models\Orders\OrderStatusHistory;
use Illuminate\Support\Facades\DB;

class UpdateOrderStatusAction
{
    public function execute(Order $order, OrderStatus $status, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $status, $note): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status === $status) {
                return $order;
            }

            $order->update([
                'status' => $status,
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => $status->value,
                'note' => $note !== null && $note !== ''
                    ? $note
                    : 'Estado actualizado desde el panel admin',
                'created_at' => now(),
            ]);

            return $order->fresh(['statusHistory']);
        });
    }
}
