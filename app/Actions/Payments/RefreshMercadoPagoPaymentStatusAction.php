<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentRecordStatus;
use App\Models\Orders\Order;
use App\Models\Orders\Payment;
use App\Services\Payments\MercadoPago\Exceptions\MercadoPagoApiException;
use App\Services\Payments\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Facades\Log;

class RefreshMercadoPagoPaymentStatusAction
{
    public function __construct(
        private readonly MercadoPagoClient $mercadoPago,
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
    ) {}

    /**
     * Reconsulta Mercado Pago y actualiza el pago local.
     *
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

        if ($payment === null || $payment->provider !== 'mercadopago' || blank($payment->mp_payment_id)) {
            return [
                'order' => $order,
                'payment' => $payment,
                'paid' => false,
                'status' => $payment?->status->value ?? 'missing',
            ];
        }

        try {
            $remote = $this->mercadoPago->getPayment((string) $payment->mp_payment_id);
        } catch (MercadoPagoApiException $e) {
            Log::warning('Mercado Pago refresh failed', [
                'order_id' => $order->id,
                'mp_payment_id' => $payment->mp_payment_id,
                'message' => $e->getMessage(),
            ]);

            return [
                'order' => $order,
                'payment' => $payment,
                'paid' => false,
                'status' => $payment->status->value,
            ];
        }

        $status = (string) ($remote['status'] ?? '');
        $payment->update(['provider_payload' => $remote]);

        if ($status === 'approved') {
            $payment->update([
                'status' => PaymentRecordStatus::Paid,
                'paid_at' => now(),
            ]);

            $this->markOrderAsPaid->execute(
                $order,
                $payment,
                'Pago Mercado Pago confirmado '.$payment->mp_payment_id,
            );

            return [
                'order' => $order->fresh(),
                'payment' => $payment->fresh(),
                'paid' => true,
                'status' => 'paid',
            ];
        }

        if (in_array($status, ['rejected', 'cancelled', 'refunded'], true)) {
            $payment->update([
                'status' => $status === 'refunded'
                    ? PaymentRecordStatus::Refunded
                    : PaymentRecordStatus::Failed,
            ]);

            if ($order->payment_status !== PaymentStatus::Paid) {
                $order->update([
                    'payment_status' => $status === 'refunded'
                        ? PaymentStatus::Refunded
                        : PaymentStatus::Failed,
                ]);
            }

            return [
                'order' => $order->fresh(),
                'payment' => $payment->fresh(),
                'paid' => false,
                'status' => $status,
            ];
        }

        return [
            'order' => $order,
            'payment' => $payment->fresh(),
            'paid' => false,
            'status' => $status !== '' ? $status : 'pending',
        ];
    }
}
