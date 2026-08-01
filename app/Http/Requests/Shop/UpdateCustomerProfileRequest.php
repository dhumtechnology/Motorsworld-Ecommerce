<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $userId = $this->user()?->id;

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'document' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customer_profiles', 'document')->ignore($userId, 'user_id'),
            ],
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
            'email' => 'correo electrónico',
            'document' => 'documento',
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'phone' => 'teléfono',
        ];
    }

    /**
     * @return array{
     *     email: string,
     *     document: string,
     *     first_name: string,
     *     last_name: string,
     *     phone: string|null
     * }
     */
    public function profileAttributes(): array
    {
        $phone = trim($this->string('phone')->value());

        return [
            'email' => $this->string('email')->lower()->value(),
            'document' => $this->string('document')->value(),
            'first_name' => $this->string('first_name')->value(),
            'last_name' => $this->string('last_name')->value(),
            'phone' => $phone !== '' ? $phone : null,
        ];
    }
}
