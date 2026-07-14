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
     * @return array{order: Order, payment: Payment, culqi: array<string, mixed>}
     *
     * @throws ValidationException
     * @throws CulqiApiException
     */
    public function execute(
        Order $order,
        PaymentMethod $method,
        ?string $culqiToken = null,
        array $customer = [],
    ): array {
        if ($order->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'order' => 'Este pedido ya está pagado.',
            ]);
        }

        $order->loadMissing(['user.customerProfile', 'shippingAddress']);

        if ($method->requiresCulqiToken()) {
            $this->assertValidToken($method, $culqiToken);
        }

        $amountCents = $this->amountInCents($order);

        if ($method === PaymentMethod::Plin && ($amountCents < 600 || $amountCents > 50000)) {
            throw ValidationException::withMessages([
                'method' => 'Plin solo acepta montos entre S/ 6.00 y S/ 500.00.',
            ]);
        }

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
            $culqiResponse = match ($method) {
                PaymentMethod::Card, PaymentMethod::Yape => $this->charge(
                    $order,
                    $payment,
                    $culqiToken,
                    $customer,
                ),
                PaymentMethod::PagoEfectivo, PaymentMethod::Plin => $this->createPaymentOrder(
                    $order,
                    $payment,
                    $method,
                    $customer,
                ),
            };
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

        return [
            'order' => $order->fresh(['items.product', 'payments']),
            'payment' => $payment->fresh(),
            'culqi' => $culqiResponse,
        ];
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    private function charge(
        Order $order,
        Payment $payment,
        string $culqiToken,
        array $customer,
    ): array {
        $user = $order->user;
        $profile = $user?->customerProfile;

        $payload = [
            'amount' => $payment->amount_cents,
            'currency_code' => $order->currency,
            'email' => $user->email,
            'source_id' => $culqiToken,
            'capture' => true,
            'description' => 'Pedido #'.$order->id.' — Motosworld',
            'metadata' => [
                'order_id' => (string) $order->id,
                'payment_id' => (string) $payment->id,
            ],
            'antifraud_details' => array_filter([
                'first_name' => $customer['first_name'] ?? $profile?->first_name,
                'last_name' => $customer['last_name'] ?? $profile?->last_name,
                'phone_number' => $this->normalizePhone($customer['phone'] ?? $profile?->phone),
                'address' => $customer['address'] ?? $order->shippingAddress?->line1,
                'address_city' => $customer['city'] ?? $order->shippingAddress?->city ?? 'Lima',
                'country_code' => 'PE',
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        $response = $this->culqi->createCharge($payload);

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

        return $response;
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    private function createPaymentOrder(
        Order $order,
        Payment $payment,
        PaymentMethod $method,
        array $customer,
    ): array {
        $user = $order->user;
        $profile = $user?->customerProfile;

        $firstName = $customer['first_name'] ?? $profile?->first_name;
        $lastName = $customer['last_name'] ?? $profile?->last_name;
        $phone = $this->normalizePhone($customer['phone'] ?? $profile?->phone);

        if ($firstName === null || $lastName === null || $phone === null) {
            throw ValidationException::withMessages([
                'customer' => 'Para PagoEfectivo/Plin se requieren nombre, apellido y teléfono.',
            ]);
        }

        $expirationHours = (int) config('services.culqi.order_expiration_hours', 24);

        $payload = [
            'amount' => $payment->amount_cents,
            'currency_code' => $order->currency,
            'description' => 'Pedido #'.$order->id.' — Motosworld',
            'order_number' => 'MW-'.$order->id.'-'.now()->timestamp,
            'client_details' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user->email,
                'phone_number' => $phone,
            ],
            'expiration_date' => now()->addHours($expirationHours)->timestamp,
            'confirm' => true,
            'metadata' => [
                'order_id' => (string) $order->id,
                'payment_id' => (string) $payment->id,
                'method' => $method->value,
            ],
        ];

        $response = $this->culqi->createOrder($payload);

        $payment->update([
            'culqi_order_id' => $response['id'] ?? null,
            'payment_code' => $response['payment_code'] ?? null,
            'qr_url' => $response['qr'] ?? null,
            'payment_url' => $response['url_pe'] ?? null,
            'expires_at' => isset($response['expiration_date'])
                ? now()->setTimestamp((int) $response['expiration_date'])
                : now()->addHours($expirationHours),
            'provider_payload' => $response,
            'status' => PaymentRecordStatus::Pending,
        ]);

        return $response;
    }

    private function amountInCents(Order $order): int
    {
        return (int) round(((float) $order->total_amount) * 100);
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
