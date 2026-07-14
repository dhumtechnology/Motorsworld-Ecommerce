<?php

namespace App\Http\Requests\Shop;

use App\Enums\Payments\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutPayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'culqi_token' => ['nullable', 'string', 'max:64'],
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::from($this->string('payment_method')->toString());
    }

    public function culqiToken(): ?string
    {
        $token = trim((string) $this->input('culqi_token', ''));

        return $token === '' ? null : $token;
    }

    /**
     * @return array{first_name: ?string, last_name: ?string, phone: ?string, address: ?string, city: ?string}
     */
    public function customerDetails(): array
    {
        return [
            'first_name' => $this->nullableString('first_name'),
            'last_name' => $this->nullableString('last_name'),
            'phone' => $this->nullableString('phone'),
            'address' => $this->nullableString('address_line1'),
            'city' => $this->nullableString('address_city'),
        ];
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
