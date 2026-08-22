<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'phone' => 'teléfono',
        ];
    }

    /**
     * Correo y documento no se aceptan: son inmutables desde la cuenta.
     *
     * @return array{
     *     first_name: string,
     *     last_name: string,
     *     phone: string|null
     * }
     */
    public function profileAttributes(): array
    {
        $phone = trim($this->string('phone')->value());

        return [
            'first_name' => $this->string('first_name')->value(),
            'last_name' => $this->string('last_name')->value(),
            'phone' => $phone !== '' ? $phone : null,
        ];
    }
}
