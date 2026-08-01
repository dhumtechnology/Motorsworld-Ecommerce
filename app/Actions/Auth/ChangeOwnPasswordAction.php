<?php

namespace App\Actions\Auth;

use App\Models\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangeOwnPasswordAction
{
    public function execute(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => 'La contraseña actual no es correcta.',
            ]);
        }

        $user->forceFill([
            'password_hash' => $newPassword,
        ])->save();
    }
}
