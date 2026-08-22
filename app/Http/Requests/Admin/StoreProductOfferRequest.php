<?php

namespace App\Http\Requests\Admin;

use App\Models\Products\Product;
use App\Support\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductOfferRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'discount_percent' => ['required', 'numeric', 'min:1', 'max:99.99'],
            'reason' => ['required', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'size:3'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Debes seleccionar un producto.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'discount_percent.required' => 'El porcentaje de descuento es obligatorio.',
            'discount_percent.min' => 'El descuento debe ser al menos 1%.',
            'discount_percent.max' => 'El descuento no puede ser 100% o más.',
            'reason.required' => 'El motivo de la oferta es obligatorio.',
            'reason.max' => 'El motivo no puede superar 500 caracteres.',
            'starts_at.required' => 'La fecha de inicio es obligatoria.',
            'ends_at.required' => 'La fecha de fin es obligatoria.',
            'ends_at.after' => 'La fecha de fin debe ser posterior al inicio.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $product = Product::query()->find($this->input('product_id'));
            if ($product === null) {
                return;
            }

            $listPrice = (float) $product->price_amount;
            if ($listPrice <= 0) {
                $validator->errors()->add('product_id', 'El producto no tiene un precio de lista válido.');

                return;
            }

            $offerPrice = $this->calculatedOfferPrice($listPrice);
            if ($offerPrice < 0.01) {
                $validator->errors()->add(
                    'discount_percent',
                    'Con ese descuento el precio de oferta queda en 0. Reduce el porcentaje.',
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function offerAttributes(): array
    {
        $product = Product::query()->findOrFail($this->input('product_id'));
        $listPrice = (float) $product->price_amount;
        $discount = round((float) $this->input('discount_percent'), 2);

        return [
            'product_id' => (int) $product->id,
            'discount_percent' => $discount,
            'offer_price_amount' => $this->calculatedOfferPrice($listPrice),
            'reason' => trim((string) $this->input('reason')),
            'currency' => Currency::normalize($product->currency),
            'starts_at' => $this->input('starts_at'),
            'ends_at' => $this->input('ends_at'),
        ];
    }

    private function calculatedOfferPrice(float $listPrice): float
    {
        $discount = (float) $this->input('discount_percent');

        return round($listPrice * (1 - ($discount / 100)), 2);
    }
}
