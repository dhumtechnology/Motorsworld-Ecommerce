<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Support\Currency;

trait ValidatesShippingRateForm
{
    /**
     * @return array<string, mixed>
     */
    protected function shippingRateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'in:PEN,USD'],
            'is_fallback' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'district_ids' => ['nullable', 'array'],
            'district_ids.*' => ['integer', 'exists:districts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function shippingRateMessages(): array
    {
        return [
            'name.required' => 'El nombre de la zona es obligatorio.',
            'price.required' => 'El precio es obligatorio.',
            'price.min' => 'El precio no puede ser negativo.',
            'currency.required' => 'La moneda es obligatoria.',
            'currency.in' => 'La moneda debe ser PEN o USD.',
        ];
    }

    protected function prepareShippingRateInput(): void
    {
        $this->merge([
            'is_fallback' => $this->boolean('is_fallback'),
            'is_active' => $this->boolean('is_active'),
            'district_ids' => array_values(array_unique(array_map('intval', $this->input('district_ids', [])))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function shippingRateAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'price' => round((float) $this->input('price'), 2),
            'currency' => Currency::normalize($this->input('currency')),
            'is_fallback' => $this->boolean('is_fallback'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => (int) ($this->input('sort_order') ?? 0),
        ];
    }

    /**
     * @return list<int>
     */
    public function districtIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->input('district_ids', []))));
    }
}
