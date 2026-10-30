<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class CapturePaypalCheckoutRequest extends FormRequest
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
            'paypal_order_id' => ['required', 'string', 'max:64'],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ];
    }

    public function paypalOrderId(): string
    {
        return trim($this->string('paypal_order_id')->toString());
    }

    public function orderId(): int
    {
        return (int) $this->input('order_id');
    }
}
