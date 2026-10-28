<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\Coupons\CouponType;
use App\Enums\Payments\PaymentMethod;
use App\Support\Currency;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesCouponForm
{
    /**
     * @return array<string, mixed>
     */
    protected function couponRules(?int $ignoreId = null): array
    {
        $isAmount = $this->input('type') === CouponType::Amount->value;
        $hasMinSubtotal = filled($this->input('min_subtotal'));

        $codeRule = Rule::unique('coupons', 'code');
        if ($ignoreId !== null) {
            $codeRule->ignore($ignoreId);
        }

        return [
            'code' => ['required', 'string', 'min:4', 'max:16', 'regex:/^[A-Z0-9\-]+$/', $codeRule],
            'name' => ['nullable', 'string', 'max:120'],
            'reason' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                $isAmount ? 'max:999999.99' : 'max:100',
            ],
            'currency' => [$isAmount ? 'required' : 'nullable', 'string', Rule::in(['PEN', 'USD'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'min_products' => ['nullable', 'integer', 'min:1', 'max:999'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'min_subtotal_currency' => [$hasMinSubtotal ? 'required' : 'nullable', Rule::in(['PEN', 'USD'])],
            'is_active' => ['nullable', 'boolean'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', 'exists:users,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'brand_ids' => ['nullable', 'array'],
            'brand_ids.*' => ['integer', 'exists:brands,id'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['string', Rule::in([
                PaymentMethod::Card->value,
                PaymentMethod::Yape->value,
            ])],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'province_ids' => ['nullable', 'array'],
            'province_ids.*' => ['integer', 'exists:provinces,id'],
            'district_ids' => ['nullable', 'array'],
            'district_ids.*' => ['integer', 'exists:districts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function couponMessages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Ya existe un cupón con ese código.',
            'code.regex' => 'El código solo puede contener letras, números y guiones.',
            'code.min' => 'El código debe tener al menos 4 caracteres.',
            'code.max' => 'El código no puede superar 16 caracteres.',
            'name.max' => 'El título no puede superar 120 caracteres.',
            'reason.max' => 'El motivo no puede superar 500 caracteres.',
            'type.required' => 'El tipo de descuento es obligatorio.',
            'value.required' => 'El valor del descuento es obligatorio.',
            'value.max' => 'El porcentaje no puede ser mayor a 100.',
            'currency.required' => 'La moneda es obligatoria para un descuento de cantidad exacta.',
            'starts_at.required' => 'La fecha de inicio es obligatoria.',
            'ends_at.required' => 'La fecha de fin es obligatoria.',
            'ends_at.after' => 'La fecha de fin debe ser posterior al inicio.',
            'min_subtotal_currency.required' => 'Indica la moneda del mínimo de compra.',
        ];
    }

    protected function prepareCouponInput(): void
    {
        $code = strtoupper(preg_replace('/\s+/', '', (string) $this->input('code', '')) ?? '');

        $this->merge([
            'code' => $code,
            'name' => $this->optionalText('name'),
            'reason' => $this->optionalText('reason'),
            'is_active' => $this->boolean('is_active'),
            'customer_ids' => array_values(array_unique(array_map('intval', $this->input('customer_ids', [])))),
            'product_ids' => array_values(array_unique(array_map('intval', $this->input('product_ids', [])))),
            'category_ids' => array_values(array_unique(array_map('intval', $this->input('category_ids', [])))),
            'brand_ids' => array_values(array_unique(array_map('intval', $this->input('brand_ids', [])))),
            'department_ids' => array_values(array_unique(array_map('intval', $this->input('department_ids', [])))),
            'province_ids' => array_values(array_unique(array_map('intval', $this->input('province_ids', [])))),
            'district_ids' => array_values(array_unique(array_map('intval', $this->input('district_ids', [])))),
            'payment_methods' => array_values(array_unique(array_filter((array) $this->input('payment_methods', [])))),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('type') === CouponType::Percent->value && (float) $this->input('value') > 100) {
                $validator->errors()->add('value', 'El porcentaje no puede ser mayor a 100.');
            }
        });
    }

    /**
     * @return array{attributes: array<string, mixed>, relations: array<string, list<int>|list<string>>}
     */
    public function couponPayload(): array
    {
        $type = CouponType::from((string) $this->input('type'));
        $isAmount = $type === CouponType::Amount;
        $minSubtotal = $this->filled('min_subtotal') ? round((float) $this->input('min_subtotal'), 2) : null;

        return [
            'attributes' => [
                'code' => (string) $this->input('code'),
                'name' => $this->optionalText('name'),
                'reason' => $this->optionalText('reason'),
                'type' => $type,
                'value' => round((float) $this->input('value'), 2),
                'currency' => $isAmount ? Currency::normalize($this->input('currency')) : null,
                'starts_at' => $this->input('starts_at'),
                'ends_at' => $this->input('ends_at'),
                'min_products' => $this->filled('min_products') ? (int) $this->input('min_products') : null,
                'min_subtotal' => $minSubtotal,
                'min_subtotal_currency' => $minSubtotal !== null
                    ? Currency::normalize($this->input('min_subtotal_currency') ?: 'PEN')
                    : null,
                'payment_methods' => $this->input('payment_methods', []) ?: null,
                'is_active' => $this->boolean('is_active'),
            ],
            'relations' => [
                'customer_ids' => array_values(array_filter($this->input('customer_ids', []))),
                'product_ids' => array_values(array_filter($this->input('product_ids', []))),
                'category_ids' => array_values(array_filter($this->input('category_ids', []))),
                'brand_ids' => array_values(array_filter($this->input('brand_ids', []))),
                'department_ids' => array_values(array_filter($this->input('department_ids', []))),
                'province_ids' => array_values(array_filter($this->input('province_ids', []))),
                'district_ids' => array_values(array_filter($this->input('district_ids', []))),
            ],
        ];
    }

    private function optionalText(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
