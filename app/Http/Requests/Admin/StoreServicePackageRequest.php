<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicePackageRequest extends FormRequest
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
            'service_type_id' => ['required', 'integer', 'exists:service_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_type_id.required' => 'El tipo de servicio es obligatorio.',
            'name.required' => 'El nombre es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function servicePackageAttributes(): array
    {
        $description = trim((string) $this->input('description', ''));
        $price = $this->input('price');
        $currency = strtoupper(trim((string) $this->input('currency', 'PEN')));

        return [
            'service_type_id' => (int) $this->input('service_type_id'),
            'name' => trim((string) $this->input('name')),
            'description' => $description !== '' ? $description : null,
            'price' => $price !== null && $price !== '' ? $price : null,
            'currency' => $currency !== '' ? $currency : 'PEN',
            'duration_minutes' => (int) ($this->input('duration_minutes') ?? 60),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
