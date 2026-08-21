<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentRecordStatus;
use App\Models\Orders\Order;
use App\Models\Orders\Payment;
use App\Services\Payments\MercadoPago\Exceptions\MercadoPagoApiException;
use App\Services\Payments\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcessMercadoPagoPaymentAction
{
    private const PENDING_POLL_ATTEMPTS = 4;

    private const PENDING_POLL_SLEEP_MS = 1500;

    public function __construct(
        private readonly MercadoPagoClient $mercadoPago,
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     * @param  array{
     *     first_name?: ?string,
     *     last_name?: ?string,
     *     phone?: ?string,
     *     email?: ?string,
     *     document?: ?string
     * }  $customer
     * @return array{order: Order, payment: Payment, mercado_pago: array<string, mixed>}
     *
     * @throws ValidationException
     * @throws MercadoPagoApiException
     */
    public function execute(
        Order $order,
        PaymentMethod $method,
        array $formData = [],
        array $customer = [],
    ): array {
        if ($order->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'order' => 'Este pedido ya está pagado.',
            ]);
        }

        if (! in_array($method, [PaymentMethod::Card, PaymentMethod::Yape], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Solo se aceptan tarjeta o Yape.',
            ]);
        }

        $order->loadMissing(['user.customerProfile']);

        $token = trim((string) ($formData['token'] ?? ''));
        if ($token === '') {
            throw ValidationException::withMessages([
                'payment' => 'No se recibió el token de pago. Vuelve a intentar.',
            ]);
        }

        $amount = round((float) $order->total_amount, 2);
        $amountCents = (int) round($amount * 100);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'mercadopago',
            'method' => $method,
            'status' => PaymentRecordStatus::Pending,
            'amount_cents' => $amountCents,
            'currency' => $order->currency,
            'source_id' => Str::limit($token, 64, ''),
        ]);

        $email = $customer['email']
            ?? data_get($formData, 'payer.email')
            ?? $order->user?->email;

        $document = $customer['document']
            ?? data_get($formData, 'payer.identification.number')
            ?? $order->user?->customerProfile?->document;

        $payload = array_filter([
            'transaction_amount' => $amount,
            'token' => $token,
            'description' => 'Pedido #'.$order->id.' — Motoworld',
            'installments' => max(1, (int) ($formData['installments'] ?? 1)),
            'payment_method_id' => $formData['payment_method_id'] ?? ($method === PaymentMethod::Yape ? 'yape' : null),
            'issuer_id' => isset($formData['issuer_id']) && $formData['issuer_id'] !== ''
                ? (int) $formData['issuer_id']
                : null,
            // En tarjeta evita estados intermedios. Yape puede quedar en revisión breve.
            'binary_mode' => $method === PaymentMethod::Card,
            'payer' => array_filter([
                'email' => $email,
                'first_name' => $customer['first_name'] ?? null,
                'last_name' => $customer['last_name'] ?? null,
                'identification' => $document
                    ? [
                        'type' => data_get($formData, 'payer.identification.type', 'DNI'),
                        'number' => (string) $document,
                    ]
                    : null,
            ], fn ($value) => $value !== null && $value !== ''),
            'external_reference' => (string) $order->id,
            'metadata' => [
                'order_id' => (string) $order->id,
                'payment_id' => (string) $payment->id,
                'method' => $method->value,
            ],
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->mercadoPago->createPayment(
                $payload,
                'mw-order-'.$order->id.'-pay-'.$payment->id,
            );
        } catch (MercadoPagoApiException $e) {
            $payment->update([
                'status' => PaymentRecordStatus::Failed,
                'provider_payload' => $e->payload,
            ]);

            $order->update([
                'payment_status' => PaymentStatus::Failed,
            ]);

            throw $e;
        }

        $response = $this->resolvePaymentStatus($response);
        $status = (string) ($response['status'] ?? '');
        $mpId = isset($response['id']) ? (string) $response['id'] : null;

        Log::info('Mercado Pago payment result', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'mp_payment_id' => $mpId,
            'status' => $status,
            'status_detail' => $response['status_detail'] ?? null,
        ]);

        if ($status === 'approved') {
            $payment->update([
                'mp_payment_id' => $mpId,
                'provider_payload' => $response,
                'status' => PaymentRecordStatus::Paid,
                'paid_at' => now(),
            ]);

            $this->markOrderAsPaid->execute(
                $order,
                $payment,
                'Pago Mercado Pago '.$mpId,
            );
        } elseif (in_array($status, ['in_process', 'pending', 'authorized'], true)) {
            $payment->update([
                'mp_payment_id' => $mpId,
                'provider_payload' => $response,
                'status' => PaymentRecordStatus::Pending,
            ]);
        } else {
            $payment->update([
                'mp_payment_id' => $mpId,
                'provider_payload' => $response,
                'status' => PaymentRecordStatus::Failed,
            ]);

            $order->update([
                'payment_status' => PaymentStatus::Failed,
            ]);

            $detail = (string) ($response['status_detail'] ?? $status);
            throw MercadoPagoApiException::fromApi(
                422,
                $this->friendlyRejectionMessage($detail),
                $response,
            );
        }

        return [
            'order' => $order->fresh(['items.product', 'payments']),
            'payment' => $payment->fresh(),
            'mercado_pago' => $response,
        ];
    }

    /**
     * Si MP responde pending/in_process, reconsulta unas veces (contingencia breve).
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function resolvePaymentStatus(array $response): array
    {
        $status = (string) ($response['status'] ?? '');
        $mpId = isset($response['id']) ? (string) $response['id'] : null;

        if ($mpId === null || ! in_array($status, ['in_process', 'pending', 'authorized'], true)) {
            return $response;
        }

        for ($i = 0; $i < self::PENDING_POLL_ATTEMPTS; $i++) {
            usleep(self::PENDING_POLL_SLEEP_MS * 1000);

            try {
                $fresh = $this->mercadoPago->getPayment($mpId);
            } catch (MercadoPagoApiException) {
                continue;
            }

            $response = $fresh;
            $status = (string) ($fresh['status'] ?? '');

            if (! in_array($status, ['in_process', 'pending', 'authorized'], true)) {
                break;
            }
        }

        return $response;
    }

    private function friendlyRejectionMessage(string $detail): string
    {
        return match ($detail) {
            'cc_rejected_insufficient_amount' => 'Fondos insuficientes. Prueba otra tarjeta.',
            'cc_rejected_bad_filled_security_code' => 'CVV incorrecto.',
            'cc_rejected_bad_filled_date' => 'Fecha de vencimiento incorrecta.',
            'cc_rejected_bad_filled_other' => 'Revisa los datos de la tarjeta.',
            'cc_rejected_call_for_authorize' => 'Debes autorizar el pago con tu banco.',
            'cc_rejected_other_reason', 'rejected_by_bank', 'rejected_high_risk' => 'El pago fue rechazado. Prueba otro medio.',
            'pending_contingency' => 'El pago está en revisión. Espera un momento e intenta de nuevo.',
            default => 'El pago no fue aprobado ('.$detail.').',
        };
    }
}
