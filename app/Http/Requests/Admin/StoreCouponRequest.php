<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesCouponForm;
use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
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
        return $this->couponRules();
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
