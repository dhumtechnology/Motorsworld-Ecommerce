<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesShippingRateForm;
use Illuminate\Foundation\Http\FormRequest;

class StoreShippingRateRequest extends FormRequest
{
    use ValidatesShippingRateForm;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->shippingRateRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->shippingRateMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareShippingRateInput();
    }
}
