<?php

namespace App\Http\Requests\Shop;

use App\Enums\Payments\PaymentMethod;
use App\Http\Requests\Shop\Concerns\ResolvesCheckoutLocation;
use Illuminate\Foundation\Http\FormRequest;

class ApplyCheckoutCouponRequest extends FormRequest
{
    use ResolvesCheckoutLocation;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->checkoutContextRules(couponRequired: true);
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
        return $this->couponCodeOrNull() ?? '';
    }

    public function paymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethodOrNull();
    }
}
