<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderAsPaidAction;
use App\Enums\Orders\PaymentStatus;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentRecordStatus;
use App\Models\Orders\Order;
use App\Models\Orders\Payment;
use App\Services\Payments\Culqi\CulqiClient;
use App\Services\Payments\Culqi\Exceptions\CulqiApiException;
use Illuminate\Validation\ValidationException;

class ProcessCulqiPaymentAction
{
    public const YAPE_MAX_CENTS = 200000;

    public function __construct(
        private readonly CulqiClient $culqi,
        private readonly MarkOrderAsPaidAction $markOrderAsPaid,
    ) {}

    /**
     * @param  array{
     *     first_name?: ?string,
     *     last_name?: ?string,
     *     phone?: ?string,
     *     address?: ?string,
     *     city?: ?string
     * }  $customer
     * @param  array{
     *     device_finger_print_id?: ?string,
     *     authentication_3DS?: ?array<string, mixed>
     * }  $threeDS
     * @return array{order: Order, payment: Payment, culqi: array<string, mixed>, needs_3ds: bool}
     *
     * @throws ValidationException
     * @throws CulqiApiException
     */
    public function execute(
        Order $order,
        PaymentMethod $method,
        ?string $culqiToken = null,
        array $customer = [],
        array $threeDS = [],
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

        $order->loadMissing(['user.customerProfile', 'shippingAddress']);

        $this->assertValidToken($method, $culqiToken);
        $this->assertYapeLimits($order, $method);

        $amountCents = $this->amountInCents($order);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'culqi',
            'method' => $method,
            'status' => PaymentRecordStatus::Pending,
            'amount_cents' => $amountCents,
            'currency' => $order->currency,
            'source_id' => $culqiToken,
        ]);

        try {
            return $this->charge($order, $payment, (string) $culqiToken, $customer, $threeDS);
        } catch (CulqiApiException $e) {
            $payment->update([
                'status' => PaymentRecordStatus::Failed,
                'provider_payload' => $e->payload,
            ]);

            $order->update([
                'payment_status' => PaymentStatus::Failed,
            ]);

            throw $e;
        }
    }

    /**
     * Segundo intento de cargo tras autenticación 3DS (mismo token y device id).
     *
     * @param  array<string, mixed>  $authentication3DS
     * @return array{order: Order, payment: Payment, culqi: array<string, mixed>, needs_3ds: bool}
     *
     * @throws ValidationException
     * @throws CulqiApiException
     */
    public function confirmThreeDS(Order $order, array $authentication3DS, array $customer = []): array
    {
        $order->loadMissing(['user.customerProfile', 'shippingAddress', 'payments']);
        $payment = $order->latestPayment();

        if ($payment === null || $payment->provider !== 'culqi') {
            throw ValidationException::withMessages([
                'payment' => 'No hay un pago Culqi pendiente de autenticación.',
            ]);
        }

        if ($payment->isPaid()) {
            return [
                'order' => $order,
                'payment' => $payment,
                'culqi' => $payment->provider_payload ?? [],
                'needs_3ds' => false,
            ];
        }

        $token = (string) $payment->source_id;
        if ($token === '' || ! str_starts_with($token, 'tkn_')) {
            throw ValidationException::withMessages([
                'culqi_token' => 'El pago pendiente no tiene un token de tarjeta válido.',
            ]);
        }

        $stored = is_array($payment->provider_payload) ? $payment->provider_payload : [];
        $deviceId = $stored['device_finger_print_id'] ?? null;

        try {
            return $this->charge($order, $payment, $token, $customer, [
                'device_finger_print_id' => is_string($deviceId) ? $deviceId : null,
                'authentication_3DS' => $authentication3DS,
            ]);
        } catch (CulqiApiException $e) {
            $payment->update([
                'status' => PaymentRecordStatus::Failed,
                'provider_payload' => $e->payload,
            ]);

            $order->update([
                'payment_status' => PaymentStatus::Failed,
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  array{
     *     device_finger_print_id?: ?string,
     *     authentication_3DS?: ?array<string, mixed>
     * }  $threeDS
     * @return array{order: Order, payment: Payment, culqi: array<string, mixed>, needs_3ds: bool}
     */
    private function charge(
        Order $order,
        Payment $payment,
        string $culqiToken,
        array $customer,
        array $threeDS = [],
    ): array {
        $user = $order->user;
        $profile = $user?->customerProfile;
        $deviceId = trim((string) ($threeDS['device_finger_print_id'] ?? ''));
        $authentication3DS = $threeDS['authentication_3DS'] ?? null;

        $antifraud = array_filter([
            'first_name' => $customer['first_name'] ?? $profile?->first_name,
            'last_name' => $customer['last_name'] ?? $profile?->last_name,
            'phone_number' => $this->normalizePhone($customer['phone'] ?? $profile?->phone),
            'address' => $customer['address'] ?? $order->shippingAddress?->line1,
            'address_city' => $customer['city'] ?? $order->shippingAddress?->city ?? 'Lima',
            'country_code' => 'PE',
            'device_finger_print_id' => $deviceId !== '' ? $deviceId : null,
        ], fn ($value) => $value !== null && $value !== '');

        $payload = [
            'amount' => $payment->amount_cents,
            'currency_code' => strtoupper((string) $order->currency),
            'email' => $user->email,
            'source_id' => $culqiToken,
            'capture' => true,
            'description' => 'Pedido #'.$order->id.' — Motoworld',
            'metadata' => [
                'order_id' => (string) $order->id,
                'payment_id' => (string) $payment->id,
            ],
            'antifraud_details' => $antifraud,
        ];

        if (is_array($authentication3DS) && $authentication3DS !== []) {
            $payload['authentication_3DS'] = array_filter([
                'eci' => $authentication3DS['eci'] ?? null,
                'xid' => $authentication3DS['xid'] ?? null,
                'cavv' => $authentication3DS['cavv'] ?? null,
                'protocolVersion' => $authentication3DS['protocolVersion'] ?? null,
                'directoryServerTransactionId' => $authentication3DS['directoryServerTransactionId'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        $result = $this->culqi->createCharge($payload);
        $response = $result['body'];

        if ($result['needs_3ds']) {
            $payment->update([
                'provider_payload' => [
                    ...$response,
                    'device_finger_print_id' => $deviceId !== '' ? $deviceId : null,
                    'needs_3ds' => true,
                ],
                'status' => PaymentRecordStatus::Pending,
            ]);

            return [
                'order' => $order->fresh(['items.product', 'payments']),
                'payment' => $payment->fresh(),
                'culqi' => $response,
                'needs_3ds' => true,
            ];
        }

        if (! $this->culqi->isSuccessfulCharge($response)) {
            throw CulqiApiException::fromApi(
                $result['http_status'],
                (string) ($response['user_message'] ?? $response['merchant_message'] ?? 'El cargo no fue aprobado.'),
                $response,
            );
        }

        $payment->update([
            'culqi_charge_id' => $response['id'] ?? null,
            'provider_payload' => $response,
            'status' => PaymentRecordStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->markOrderAsPaid->execute(
            $order,
            $payment,
            'Pago Culqi cargo '.($response['id'] ?? ''),
        );

        return [
            'order' => $order->fresh(['items.product', 'payments']),
            'payment' => $payment->fresh(),
            'culqi' => $response,
            'needs_3ds' => false,
        ];
    }

    private function amountInCents(Order $order): int
    {
        return (int) round(((float) $order->total_amount) * 100);
    }

    private function assertYapeLimits(Order $order, PaymentMethod $method): void
    {
        if ($method !== PaymentMethod::Yape) {
            return;
        }

        if (strtoupper((string) $order->currency) !== 'PEN') {
            throw ValidationException::withMessages([
                'method' => 'Yape solo acepta pagos en soles (PEN).',
            ]);
        }

        $cents = $this->amountInCents($order);

        if ($cents > self::YAPE_MAX_CENTS) {
            throw ValidationException::withMessages([
                'method' => 'Yape acepta un máximo de S/ 2,000.00 por transacción.',
            ]);
        }

        if ($cents < 100) {
            throw ValidationException::withMessages([
                'method' => 'El monto mínimo para pagar con Yape es S/ 1.00.',
            ]);
        }
    }

    private function assertValidToken(PaymentMethod $method, ?string $token): void
    {
        if ($token === null || $token === '') {
            throw ValidationException::withMessages([
                'culqi_token' => 'Se requiere un token Culqi para pagar con '.$method->label().'.',
            ]);
        }

        $prefix = $method === PaymentMethod::Yape ? 'ype_' : 'tkn_';

        if (! str_starts_with($token, $prefix)) {
            throw ValidationException::withMessages([
                'culqi_token' => "El token debe comenzar con {$prefix} para pagos con {$method->label()}.",
            ]);
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '51') && strlen($digits) === 11) {
            $digits = substr($digits, 2);
        }

        return $digits !== '' ? $digits : null;
    }
}
