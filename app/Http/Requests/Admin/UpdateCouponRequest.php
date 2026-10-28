<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesCouponForm;
use App\Models\Coupons\Coupon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
{
    use ValidatesCouponForm;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Coupon $coupon */
        $coupon = $this->route('coupon');

        return $this->couponRules($coupon->id);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->couponMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCouponInput();
    }
}
