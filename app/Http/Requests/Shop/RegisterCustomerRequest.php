<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'document' => ['required', 'string', 'max:50', 'unique:customer_profiles,document'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->lower()->value();
    }

    public function password(): string
    {
        return $this->string('password')->value();
    }

    public function document(): string
    {
        return $this->string('document')->value();
    }

    public function firstName(): string
    {
        return $this->string('first_name')->value();
    }

    public function lastName(): string
    {
        return $this->string('last_name')->value();
    }

    public function phone(): ?string
    {
        $phone = trim($this->string('phone')->value());

        return $phone !== '' ? $phone : null;
    }
}
