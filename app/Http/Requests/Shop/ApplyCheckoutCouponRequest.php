<?php

namespace App\Http\Requests\Shop;

use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Payments\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyCheckoutCouponRequest extends FormRequest
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
        return [
            'coupon_code' => ['required', 'string', 'max:16'],
            'fulfillment_method' => ['nullable', Rule::enum(FulfillmentMethod::class)],
            'address_city' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', Rule::in([
                PaymentMethod::Card->value,
                PaymentMethod::Yape->value,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'coupon_code' => 'cupón',
        ];
    }

    public function couponCode(): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $this->input('coupon_code', '')) ?? '');
    }

    public function fulfillmentMethod(): ?FulfillmentMethod
    {
        $value = $this->input('fulfillment_method');

        return is_string($value) && $value !== '' ? FulfillmentMethod::tryFrom($value) : null;
    }

    public function city(): ?string
    {
        $city = trim((string) $this->input('address_city', ''));

        return $city === '' ? null : $city;
    }

    public function paymentMethod(): ?PaymentMethod
    {
        $value = $this->input('payment_method');

        return is_string($value) && $value !== '' ? PaymentMethod::tryFrom($value) : null;
    }
}
