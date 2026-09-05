<?php

namespace App\Http\Requests\Shop;

use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Payments\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutPayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $authenticated = $this->user() !== null;
        $hasDocument = filled($this->user()?->customerProfile?->document);
        $isDelivery = $this->input('fulfillment_method') === FulfillmentMethod::Delivery->value;

        return [
            'payment_method' => ['required', 'string', Rule::in([
                PaymentMethod::Card->value,
                PaymentMethod::Yape->value,
            ])],
            'culqi_token' => ['required', 'string', 'max:80'],
            'device_finger_print_id' => ['nullable', 'string', 'max:80'],
            'authentication_3DS' => ['nullable', 'array'],
            'authentication_3DS.eci' => ['nullable', 'string', 'max:8'],
            'authentication_3DS.xid' => ['nullable', 'string', 'max:80'],
            'authentication_3DS.cavv' => ['nullable', 'string', 'max:80'],
            'authentication_3DS.protocolVersion' => ['nullable', 'string', 'max:16'],
            'authentication_3DS.directoryServerTransactionId' => ['nullable', 'string', 'max:80'],
            'fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)],
            'customer_email' => [$authenticated ? 'nullable' : 'required', 'email', 'max:255'],
            'customer_document' => [
                $authenticated && $hasDocument ? 'nullable' : 'required',
                'string',
                'max:20',
            ],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line1' => [$isDelivery ? 'required' : 'nullable', 'string', 'max:255'],
            'address_city' => [$isDelivery ? 'required' : 'nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fulfillment_method' => 'método de entrega',
            'customer_email' => 'correo',
            'customer_document' => 'documento',
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'phone' => 'teléfono',
            'address_line1' => 'dirección',
            'address_city' => 'ciudad',
            'culqi_token' => 'token de pago',
        ];
    }

    public function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::from($this->string('payment_method')->toString());
    }

    public function fulfillmentMethod(): FulfillmentMethod
    {
        return FulfillmentMethod::from($this->string('fulfillment_method')->toString());
    }

    public function culqiToken(): string
    {
        return trim((string) $this->input('culqi_token', ''));
    }

    /**
     * @return array{
     *     device_finger_print_id?: ?string,
     *     authentication_3DS?: array<string, mixed>
     * }
     */
    public function threeDS(): array
    {
        $deviceId = trim((string) $this->input('device_finger_print_id', ''));
        $auth = $this->input('authentication_3DS');

        return array_filter([
            'device_finger_print_id' => $deviceId !== '' ? $deviceId : null,
            'authentication_3DS' => is_array($auth) ? $auth : null,
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * @return array{first_name: ?string, last_name: ?string, phone: ?string, address: ?string, city: ?string, email: ?string, document: ?string}
     */
    public function customerDetails(): array
    {
        $payload = $this->customerPayload();

        return [
            'first_name' => $this->nullableString('first_name'),
            'last_name' => $this->nullableString('last_name'),
            'phone' => $this->nullableString('phone'),
            'address' => $this->nullableString('address_line1'),
            'city' => $this->nullableString('address_city'),
            'email' => $payload['customer_email'] !== '' ? $payload['customer_email'] : null,
            'document' => $payload['customer_document'] !== '' ? $payload['customer_document'] : null,
        ];
    }

    /**
     * @return array{
     *     customer_name: string,
     *     customer_document: string,
     *     customer_phone: string,
     *     customer_email: string
     * }
     */
    public function customerPayload(): array
    {
        $user = $this->user();
        $profile = $user?->customerProfile;

        $email = $this->nullableString('customer_email')
            ?? ($user?->email ? strtolower($user->email) : null);

        $document = $this->nullableString('customer_document')
            ?? ($profile?->document ? trim((string) $profile->document) : null);

        $firstName = trim((string) $this->input('first_name', ''));
        $lastName = trim((string) $this->input('last_name', ''));
        $fullName = trim($firstName.' '.$lastName);

        if ($fullName === '') {
            $fullName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? ''));
        }

        return [
            'customer_name' => $fullName !== '' ? $fullName : 'Cliente Motoworld',
            'customer_document' => $document ?? '',
            'customer_phone' => $this->nullableString('phone') ?? (string) ($profile?->phone ?? ''),
            'customer_email' => $email ?? '',
        ];
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
