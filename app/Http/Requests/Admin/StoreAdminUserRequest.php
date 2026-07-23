<?php

namespace App\Http\Requests\Admin;

use App\Enums\Auth\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminUserRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'Ingresa un email válido.',
            'email.unique' => 'Ya existe un usuario con ese email.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'status.required' => 'El estado es obligatorio.',
        ];
    }

    /**
     * @return array{email: string, status: UserStatus, password: string}
     */
    public function adminUserAttributes(): array
    {
        return [
            'email' => $this->string('email')->lower()->value(),
            'status' => UserStatus::from((string) $this->input('status')),
            'password' => $this->string('password')->value(),
        ];
    }
}
