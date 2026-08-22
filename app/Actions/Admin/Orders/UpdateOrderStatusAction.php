<?php

namespace App\Actions\Admin\Orders;

use App\Enums\Orders\OrderStatus;
use App\Mail\OrderStatusChangedMail;
use App\Models\Orders\Order;
use App\Models\Orders\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class UpdateOrderStatusAction
{
    /** @var list<OrderStatus> */
    private const NOTIFY_STATUSES = [
        OrderStatus::Shipped,
        OrderStatus::Delivered,
        OrderStatus::Cancelled,
        OrderStatus::Refunded,
    ];

    public function execute(Order $order, OrderStatus $status, ?string $note = null): Order
    {
        $changed = false;
        $notifyNote = $note;

        $order = DB::transaction(function () use ($order, $status, $note, &$changed): Order {
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

            $changed = true;

            return $order->fresh([
                'statusHistory',
                'user.customerProfile',
                'shippingAddress',
                'items.product',
                'items.variant',
            ]);
        });

        if ($changed && in_array($status, self::NOTIFY_STATUSES, true)) {
            $this->notifyCustomer($order, $notifyNote);
        }

        return $order;
    }

    private function notifyCustomer(Order $order, ?string $note): void
    {
        $email = $order->user?->email;

        if (! filled($email)) {
            return;
        }

        $mailNote = filled($note) ? trim((string) $note) : null;

        // No exponer la nota genérica del admin en el correo.
        if ($mailNote === 'Estado actualizado desde el panel admin') {
            $mailNote = null;
        }

        try {
            Mail::to($email)->send(new OrderStatusChangedMail($order, $mailNote));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
