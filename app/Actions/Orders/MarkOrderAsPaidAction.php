<?php

namespace App\Actions\Orders;

use App\Actions\Inventory\RegisterInventoryMovementAction;
use App\Enums\Orders\OrderStatus;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentRecordStatus;
use App\Mail\OrderPaidConfirmationMail;
use App\Models\Orders\Order;
use App\Models\Orders\OrderStatusHistory;
use App\Models\Orders\Payment;
use App\Models\Products\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MarkOrderAsPaidAction
{
    public function __construct(
        private readonly RegisterInventoryMovementAction $registerInventoryMovement,
    ) {}

    public function execute(Order $order, ?Payment $payment = null, ?string $note = null): Order
    {
        $markedNow = false;

        $order = DB::transaction(function () use ($order, $payment, $note, &$markedNow) {
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

            $this->registerSaleExits($order);

            $markedNow = true;

            return $order->fresh([
                'items.product',
                'items.variant',
                'payments',
                'user.customerProfile',
                'shippingAddress',
            ]);
        });

        if ($markedNow) {
            $this->sendConfirmationMail($order);
        }

        return $order;
    }

    private function sendConfirmationMail(Order $order): void
    {
        $email = $order->user?->email;

        if (! filled($email)) {
            return;
        }

        try {
            Mail::to($email)->send(new OrderPaidConfirmationMail($order));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function registerSaleExits(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $alreadyRegistered = InventoryMovement::query()
                ->where('order_item_id', $item->id)
                ->where('reason', 'sale')
                ->exists();

            if ($alreadyRegistered) {
                continue;
            }

            $this->registerInventoryMovement->registerSaleExit($order, $item);
        }
    }
}
