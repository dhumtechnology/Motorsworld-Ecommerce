<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Enums\Payments\PaymentRecordStatus;
use App\Models\Orders\Payment;
use Illuminate\Support\Facades\Log;

class HandleCulqiWebhookAction
{
    public function __construct(
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
    ) {}

    /**
     * @param  array<string, mixed>  $event
     */
    public function execute(array $event): void
    {
        $type = (string) ($event['type'] ?? '');
        $data = $event['data'] ?? null;

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($data)) {
            Log::warning('Culqi webhook sin data válida', ['event' => $event]);

            return;
        }

        match (true) {
            $type === 'order.status.changed' => $this->handleOrderStatusChanged($data),
            $type === 'charge.creation.succeeded' => $this->handleChargeSucceeded($data),
            default => Log::info('Culqi webhook ignorado', ['type' => $type]),
        };
    }

    /**
     * @param  array<string, mixed>  $orderData
     */
    private function handleOrderStatusChanged(array $orderData): void
    {
        $culqiOrderId = $orderData['id'] ?? null;
        $state = strtolower((string) ($orderData['state'] ?? ''));

        if (! is_string($culqiOrderId) || $culqiOrderId === '') {
            return;
        }

        $payment = Payment::query()
            ->where('culqi_order_id', $culqiOrderId)
            ->latest('id')
            ->first();

        if ($payment === null) {
            $metadataOrderId = $orderData['metadata']['order_id'] ?? null;
            if ($metadataOrderId) {
                $payment = Payment::query()
                    ->where('order_id', (int) $metadataOrderId)
                    ->where('culqi_order_id', $culqiOrderId)
                    ->latest('id')
                    ->first()
                    ?? Payment::query()
                        ->where('order_id', (int) $metadataOrderId)
                        ->latest('id')
                        ->first();
            }
        }

        if ($payment === null) {
            Log::warning('Culqi webhook: payment no encontrado para orden', [
                'culqi_order_id' => $culqiOrderId,
            ]);

            return;
        }

        if (in_array($state, ['paid', 'pagado'], true)) {
            $payment->update(['provider_payload' => $orderData]);
            $this->markOrderAsPaid->execute(
                $payment->order,
                $payment,
                'Pago confirmado vía webhook Culqi (order.status.changed)',
            );

            return;
        }

        if (in_array($state, ['expired', 'expirado'], true)) {
            $payment->update([
                'status' => PaymentRecordStatus::Expired,
                'provider_payload' => $orderData,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $chargeData
     */
    private function handleChargeSucceeded(array $chargeData): void
    {
        $chargeId = $chargeData['id'] ?? null;
        $metadataOrderId = $chargeData['metadata']['order_id'] ?? null;
        $metadataPaymentId = $chargeData['metadata']['payment_id'] ?? null;

        $payment = null;

        if (is_string($chargeId) && $chargeId !== '') {
            $payment = Payment::query()->where('culqi_charge_id', $chargeId)->first();
        }

        if ($payment === null && $metadataPaymentId) {
            $payment = Payment::query()->find((int) $metadataPaymentId);
        }

        if ($payment === null && $metadataOrderId) {
            $payment = Payment::query()
                ->where('order_id', (int) $metadataOrderId)
                ->latest('id')
                ->first();
        }

        if ($payment === null) {
            return;
        }

        if ($payment->isPaid()) {
            return;
        }

        $payment->update([
            'culqi_charge_id' => $chargeId ?? $payment->culqi_charge_id,
            'provider_payload' => $chargeData,
        ]);

        $this->markOrderAsPaid->execute(
            $payment->order,
            $payment,
            'Pago confirmado vía webhook Culqi (charge.creation.succeeded)',
        );
    }
}
