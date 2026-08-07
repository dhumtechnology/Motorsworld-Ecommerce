<?php

namespace App\Http\Requests\Shop;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\CustomerProfile;
use App\Models\Auth\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

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
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'document' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $email = $this->email();
            $document = $this->document();

            $existingUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($existingUser !== null && $existingUser->status !== UserStatus::Pending) {
                $validator->errors()->add('email', 'Este correo ya está registrado.');
            }

            $profile = CustomerProfile::query()
                ->where('document', $document)
                ->first();

            if ($profile === null) {
                return;
            }

            $belongsToPending = $existingUser !== null
                && $existingUser->status === UserStatus::Pending
                && (int) $profile->user_id === (int) $existingUser->id;

            if (! $belongsToPending) {
                $validator->errors()->add('document', 'Este documento ya está registrado.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'password_confirmation' => 'confirmación de contraseña',
            'document' => 'documento',
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'phone' => 'teléfono',
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
