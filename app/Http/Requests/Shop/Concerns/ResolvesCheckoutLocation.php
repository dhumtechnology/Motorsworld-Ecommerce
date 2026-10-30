<?php

namespace App\Http\Requests\Shop\Concerns;

use App\Enums\Orders\FulfillmentMethod;
use App\Enums\Payments\PaymentMethod;
use App\Models\Locations\District;
use Illuminate\Validation\Rule;

trait ResolvesCheckoutLocation
{
    /**
     * @return array<string, mixed>
     */
    protected function checkoutContextRules(bool $couponRequired = false): array
    {
        return [
            'coupon_code' => [$couponRequired ? 'required' : 'nullable', 'string', 'max:16'],
            'fulfillment_method' => ['nullable', Rule::enum(FulfillmentMethod::class)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'payment_method' => ['nullable', 'string', Rule::in([
                PaymentMethod::Card->value,
                PaymentMethod::Yape->value,
                PaymentMethod::Paypal->value,
            ])],
        ];
    }

    public function fulfillmentMethod(): ?FulfillmentMethod
    {
        $value = $this->input('fulfillment_method');

        return is_string($value) && $value !== '' ? FulfillmentMethod::tryFrom($value) : null;
    }

    public function district(): ?District
    {
        $id = (int) $this->input('district_id', 0);

        if ($id <= 0) {
            return null;
        }

        return District::query()->find($id);
    }

    public function paymentMethodOrNull(): ?PaymentMethod
    {
        $value = $this->input('payment_method');

        return is_string($value) && $value !== '' ? PaymentMethod::tryFrom($value) : null;
    }

    public function couponCodeOrNull(): ?string
    {
        $code = strtoupper(preg_replace('/\s+/', '', (string) $this->input('coupon_code', '')) ?? '');

        return $code === '' ? null : $code;
    }
}
