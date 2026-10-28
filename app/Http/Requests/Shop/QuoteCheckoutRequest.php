<?php

namespace App\Http\Requests\Shop;

use App\Http\Requests\Shop\Concerns\ResolvesCheckoutLocation;
use Illuminate\Foundation\Http\FormRequest;

class QuoteCheckoutRequest extends FormRequest
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
        return $this->checkoutContextRules();
    }
}
