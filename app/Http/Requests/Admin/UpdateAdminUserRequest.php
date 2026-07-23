<?php

namespace App\Http\Requests\Admin;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminUserRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
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
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'status.required' => 'El estado es obligatorio.',
        ];
    }

    /**
     * @return array{email: string, status: UserStatus, password: string|null}
     */
    public function adminUserAttributes(): array
    {
        $password = $this->input('password');

        return [
            'email' => $this->string('email')->lower()->value(),
            'status' => UserStatus::from((string) $this->input('status')),
            'password' => is_string($password) && $password !== '' ? $password : null,
        ];
    }
}
