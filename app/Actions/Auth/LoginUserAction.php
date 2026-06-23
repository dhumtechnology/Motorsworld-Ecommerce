<?php

namespace App\Actions\Auth;

use App\Models\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    public function execute(string $email, string $password): User
    {
        $user = User::query()
            ->where('email', $email)
            ->with('roles')
            ->first();

        if (
            $user === null
            || ! Hash::check($password, $user->getAuthPassword())
        ) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        if (! $user->canAuthenticate()) {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta no está activa o no puede iniciar sesión.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }
}
