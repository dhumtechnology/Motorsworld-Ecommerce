<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentRecordStatus;
use App\Models\Orders\Order;
use App\Models\Orders\Payment;
use App\Services\Payments\Paypal\Exceptions\PaypalApiException;
use App\Services\Payments\Paypal\PaypalClient;
use App\Support\Currency;
use Illuminate\Validation\ValidationException;

class ProcessPaypalPaymentAction
{
    private const PAYPAL_CURRENCY = 'USD';

    public function __construct(
        private readonly PaypalClient $paypal,
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
    ) {}

    /**
     * @return array{order: Order, payment: Payment, paypal_order_id: string}
     */
    public function create(Order $order): array
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'order' => 'Este pedido ya está pagado.',
            ]);
        }

        $amountCents = $this->amountInCents($order);

        if ($amountCents < 100) {
            throw ValidationException::withMessages([
                'payment' => $this->minimumAmountMessage($order, $amountCents),
            ]);
        }

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'paypal',
            'method' => PaymentMethod::Paypal,
            'status' => PaymentRecordStatus::Pending,
            'amount_cents' => $amountCents,
            'currency' => self::PAYPAL_CURRENCY,
        ]);

        try {
            $paypalOrder = $this->paypal->createOrder($this->orderPayload($order, $payment));
        } catch (PaypalApiException $e) {
            $payment->update([
                'status' => PaymentRecordStatus::Failed,
                'provider_payload' => $e->payload,
            ]);
            $order->update(['payment_status' => PaymentStatus::Failed]);

            throw $e;
        }

        $paypalOrderId = trim((string) ($paypalOrder['id'] ?? ''));

        if ($paypalOrderId === '') {
            $payment->update([
                'status' => PaymentRecordStatus::Failed,
                'provider_payload' => $paypalOrder,
            ]);
            $order->update(['payment_status' => PaymentStatus::Failed]);

            throw PaypalApiException::fromApi(500, 'PayPal no devolvió un identificador de orden.', $paypalOrder);
        }

        $payment->update([
            'paypal_order_id' => $paypalOrderId,
            'provider_payload' => $paypalOrder,
        ]);

        return [
            'order' => $order->fresh(['payments']),
            'payment' => $payment->fresh(),
            'paypal_order_id' => $paypalOrderId,
        ];
    }

    /**
     * @return array{order: Order, payment: Payment}
     */
    public function capture(Order $order, string $paypalOrderId): array
    {
        $order->loadMissing(['payments']);
        $payment = $order->latestPayment();

        if ($payment === null || $payment->provider !== 'paypal') {
            throw ValidationException::withMessages([
                'payment' => 'No hay un pago PayPal pendiente para este pedido.',
            ]);
        }

        if ($payment->isPaid() && $order->payment_status === PaymentStatus::Paid) {
            return [
                'order' => $order,
                'payment' => $payment,
            ];
        }

        if ((string) $payment->paypal_order_id !== $paypalOrderId) {
            throw ValidationException::withMessages([
                'paypal_order_id' => 'La orden de PayPal no coincide con este pedido.',
            ]);
        }

        try {
            $captured = $this->paypal->captureOrder($paypalOrderId);
        } catch (PaypalApiException $e) {
            $payment->update([
                'status' => PaymentRecordStatus::Failed,
                'provider_payload' => $e->payload,
            ]);
            $order->update(['payment_status' => PaymentStatus::Failed]);

            throw $e;
        }

        $this->assertSuccessfulCapture($payment, $captured);

        $payment->update([
            'provider_payload' => $captured,
            'status' => PaymentRecordStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->markOrderAsPaid->execute(
            $order,
            $payment,
            'Pago PayPal '.$paypalOrderId,
        );

        return [
            'order' => $order->fresh(['items.product', 'payments']),
            'payment' => $payment->fresh(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order, Payment $payment): array
    {
        return [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $order->id,
                'custom_id' => (string) $payment->id,
                'description' => 'Pedido #'.$order->id.' — Motoworld',
                'amount' => [
                    'currency_code' => self::PAYPAL_CURRENCY,
                    'value' => $this->formatAmount($order),
                ],
            ]],
            'application_context' => [
                'brand_name' => 'Motoworld',
                'locale' => 'es-PE',
                'landing_page' => 'LOGIN',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $captured
     */
    private function assertSuccessfulCapture(Payment $payment, array $captured): void
    {
        $status = strtoupper((string) ($captured['status'] ?? ''));
        $capture = $this->firstCapture($captured);
        $captureStatus = strtoupper((string) ($capture['status'] ?? ''));

        if ($this->paypal->isFake()) {
            if ($status !== 'COMPLETED' && $captureStatus !== 'COMPLETED') {
                throw PaypalApiException::fromApi(422, 'PayPal no completó el cobro.', $captured);
            }

            return;
        }

        if ($status !== 'COMPLETED' && $captureStatus !== 'COMPLETED') {
            throw PaypalApiException::fromApi(
                422,
                'PayPal no completó el cobro. Estado: '.($status !== '' ? $status : 'desconocido'),
                $captured,
            );
        }

        $amount = is_array($capture['amount'] ?? null) ? $capture['amount'] : [];
        $currency = strtoupper((string) ($amount['currency_code'] ?? ''));
        $valueCents = (int) round(((float) ($amount['value'] ?? 0)) * 100);

        if ($currency !== '' && $currency !== strtoupper((string) $payment->currency)) {
            throw PaypalApiException::fromApi(422, 'La moneda capturada por PayPal no coincide con el pedido.', $captured);
        }

        if ($valueCents > 0 && $valueCents !== (int) $payment->amount_cents) {
            throw PaypalApiException::fromApi(422, 'El monto capturado por PayPal no coincide con el pedido.', $captured);
        }
    }

    /**
     * @param  array<string, mixed>  $captured
     * @return array<string, mixed>
     */
    private function firstCapture(array $captured): array
    {
        $units = $captured['purchase_units'] ?? [];
        $firstUnit = is_array($units[0] ?? null) ? $units[0] : [];
        $payments = is_array($firstUnit['payments'] ?? null) ? $firstUnit['payments'] : [];
        $captures = is_array($payments['captures'] ?? null) ? $payments['captures'] : [];
        $capture = $captures[0] ?? [];

        return is_array($capture) ? $capture : [];
    }

    private function amountInCents(Order $order): int
    {
        return (int) round($this->paypalAmount($order) * 100);
    }

    private function minimumAmountMessage(Order $order, int $amountCents): string
    {
        $chargedUsd = number_format($amountCents / 100, 2, '.', '');
        $message = 'El monto mínimo para pagar con PayPal es USD 1.00.';

        $rate = $order->exchange_rate_sell !== null ? (float) $order->exchange_rate_sell : 0;

        if (Currency::normalize($order->currency) !== self::PAYPAL_CURRENCY && $rate > 0) {
            $minInOrderCurrency = Currency::convert(1.00, self::PAYPAL_CURRENCY, $order->currency, $rate);
            $message .= ' Equivale a aprox. '.Currency::format($minInOrderCurrency, $order->currency)
                .' al tipo de cambio venta '.number_format($rate, 4, '.', '').'.';
        }

        $message .= ' Este pedido equivale a USD '.$chargedUsd.'.';

        return $message;
    }

    private function formatAmount(Order $order): string
    {
        return number_format($this->paypalAmount($order), 2, '.', '');
    }

    private function paypalAmount(Order $order): float
    {
        if (Currency::normalize($order->currency) === self::PAYPAL_CURRENCY) {
            return round((float) $order->total_amount, 2);
        }

        $rate = $order->exchange_rate_sell !== null ? (float) $order->exchange_rate_sell : 0;

        if ($rate <= 0) {
            throw ValidationException::withMessages([
                'payment' => 'No hay tipo de cambio para cobrar este pedido con PayPal (USD).',
            ]);
        }

        return Currency::convert((float) $order->total_amount, $order->currency, self::PAYPAL_CURRENCY, $rate);
    }
}
