<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
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
            'document' => 'DNI',
            'phone' => 'teléfono',
            'email' => 'email',
            'message' => 'mensaje',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contactData(): array
    {
        return [
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'document' => trim((string) $this->input('document')),
            'phone' => trim((string) $this->input('phone')),
            'email' => trim((string) $this->input('email')),
            'message' => trim((string) $this->input('message')),
        ];
    }
}
