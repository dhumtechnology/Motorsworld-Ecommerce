<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentRecordStatus;
use App\Models\Orders\Order;
use App\Models\Orders\Payment;
use App\Services\Payments\Culqi\CulqiClient;
use App\Services\Payments\Culqi\Exceptions\CulqiApiException;
use Illuminate\Support\Facades\Log;

class RefreshCulqiPaymentStatusAction
{
    public function __construct(
        private readonly CulqiClient $culqi,
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
    ) {}

    /**
     * @return array{order: Order, payment: ?Payment, paid: bool, status: string}
     */
    public function execute(Order $order): array
    {
        $order->loadMissing('payments');
        $payment = $order->latestPayment();

        if ($order->payment_status === PaymentStatus::Paid) {
            return [
                'order' => $order,
                'payment' => $payment,
                'paid' => true,
                'status' => 'paid',
            ];
        }

        if ($payment === null || $payment->provider !== 'culqi') {
            return [
                'order' => $order,
                'payment' => $payment,
                'paid' => false,
                'status' => $payment?->status->value ?? 'missing',
            ];
        }

        if (filled($payment->culqi_charge_id)) {
            return $this->refreshCharge($order, $payment, (string) $payment->culqi_charge_id);
        }

        if (filled($payment->culqi_order_id)) {
            return $this->refreshOrder($order, $payment, (string) $payment->culqi_order_id);
        }

        return [
            'order' => $order,
            'payment' => $payment,
            'paid' => false,
            'status' => $payment->status->value,
        ];
    }

    /**
     * @return array{order: Order, payment: Payment, paid: bool, status: string}
     */
    private function refreshCharge(Order $order, Payment $payment, string $chargeId): array
    {
        try {
            $remote = $this->culqi->getCharge($chargeId);
        } catch (CulqiApiException $e) {
            Log::warning('Culqi charge refresh failed', [
                'order_id' => $order->id,
                'culqi_charge_id' => $chargeId,
                'message' => $e->getMessage(),
            ]);

            return [
                'order' => $order,
                'payment' => $payment,
                'paid' => false,
                'status' => $payment->status->value,
            ];
        }

        $payment->update(['provider_payload' => $remote]);

        if ($this->culqi->isSuccessfulCharge($remote)) {
            $payment->update([
                'status' => PaymentRecordStatus::Paid,
                'paid_at' => now(),
            ]);

            $this->markOrderAsPaid->execute(
                $order,
                $payment,
                'Pago Culqi confirmado '.$chargeId,
            );

            return [
                'order' => $order->fresh(),
                'payment' => $payment->fresh(),
                'paid' => true,
                'status' => 'paid',
            ];
        }

        return [
            'order' => $order,
            'payment' => $payment->fresh(),
            'paid' => false,
            'status' => $payment->status->value,
        ];
    }

    /**
     * @return array{order: Order, payment: Payment, paid: bool, status: string}
     */
    private function refreshOrder(Order $order, Payment $payment, string $orderId): array
    {
        try {
            $remote = $this->culqi->getOrder($orderId);
        } catch (CulqiApiException $e) {
            Log::warning('Culqi order refresh failed', [
                'order_id' => $order->id,
                'culqi_order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return [
                'order' => $order,
                'payment' => $payment,
                'paid' => false,
                'status' => $payment->status->value,
            ];
        }

        $state = strtolower((string) ($remote['state'] ?? ''));
        $payment->update(['provider_payload' => $remote]);

        if (in_array($state, ['paid', 'pagado'], true)) {
            $this->markOrderAsPaid->execute(
                $order,
                $payment,
                'Pago Culqi orden confirmada '.$orderId,
            );

            return [
                'order' => $order->fresh(),
                'payment' => $payment->fresh(),
                'paid' => true,
                'status' => 'paid',
            ];
        }

        if (in_array($state, ['expired', 'expirado'], true)) {
            $payment->update(['status' => PaymentRecordStatus::Expired]);

            if ($order->payment_status !== PaymentStatus::Paid) {
                $order->update(['payment_status' => PaymentStatus::Failed]);
            }

            return [
                'order' => $order->fresh(),
                'payment' => $payment->fresh(),
                'paid' => false,
                'status' => 'expired',
            ];
        }

        return [
            'order' => $order,
            'payment' => $payment->fresh(),
            'paid' => false,
            'status' => $state !== '' ? $state : 'pending',
        ];
    }
}
