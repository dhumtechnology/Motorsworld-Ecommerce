<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentRecordStatus;
use App\Models\Orders\Payment;
use App\Services\Payments\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Facades\Log;

class HandleMercadoPagoWebhookAction
{
    public function __construct(
        private readonly MercadoPagoClient $mercadoPago,
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): void
    {
        $type = (string) ($payload['type'] ?? $payload['topic'] ?? '');
        $action = (string) ($payload['action'] ?? '');

        if (! in_array($type, ['payment', 'topic_payment'], true)
            && ! str_contains($action, 'payment')) {
            Log::info('MercadoPago webhook ignored', ['type' => $type, 'action' => $action]);

            return;
        }

        $paymentId = data_get($payload, 'data.id')
            ?? data_get($payload, 'id')
            ?? data_get($payload, 'resource');

        if (is_string($paymentId) && str_contains($paymentId, '/')) {
            $paymentId = basename($paymentId);
        }

        if ($paymentId === null || $paymentId === '') {
            Log::warning('MercadoPago webhook without payment id', $payload);

            return;
        }

        $remote = $this->mercadoPago->getPayment((string) $paymentId);
        $status = (string) ($remote['status'] ?? '');

        $payment = Payment::query()
            ->where('provider', 'mercadopago')
            ->where(function ($query) use ($paymentId, $remote) {
                $query->where('mp_payment_id', (string) $paymentId);

                $external = data_get($remote, 'external_reference');
                if ($external) {
                    $query->orWhereHas('order', fn ($q) => $q->where('id', (int) $external));
                }
            })
            ->latest('id')
            ->first();

        if ($payment === null) {
            Log::warning('MercadoPago webhook payment not found', [
                'mp_payment_id' => $paymentId,
                'external_reference' => data_get($remote, 'external_reference'),
            ]);

            return;
        }

        $payment->update([
            'mp_payment_id' => (string) ($remote['id'] ?? $paymentId),
            'provider_payload' => $remote,
        ]);

        if ($status === 'approved' && ! $payment->isPaid()) {
            $this->markOrderAsPaid->execute(
                $payment->order,
                $payment,
                'Webhook Mercado Pago '.$paymentId,
            );

            return;
        }

        if (in_array($status, ['rejected', 'cancelled', 'refunded'], true)) {
            $payment->update([
                'status' => $status === 'refunded'
                    ? PaymentRecordStatus::Refunded
                    : PaymentRecordStatus::Failed,
            ]);

            if ($payment->order && $payment->order->payment_status !== PaymentStatus::Paid) {
                $payment->order->update([
                    'payment_status' => $status === 'refunded'
                        ? PaymentStatus::Refunded
                        : PaymentStatus::Failed,
                ]);
            }
        }
    }
}
