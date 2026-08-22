<?php

namespace App\Actions\Auth;

use App\Mail\ConfirmPasswordChangeMail;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChangeOwnPasswordAction
{
    public const CACHE_PREFIX = 'password_change_confirm:';

    public const EXPIRES_MINUTES = 60;

    /**
     * Valida la contraseña actual y envía un correo con enlace de confirmación.
     * La contraseña nueva solo se aplica al confirmar el enlace.
     */
    public function execute(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => 'La contraseña actual no es correcta.',
            ]);
        }

        if (Hash::check($newPassword, $user->password_hash)) {
            throw ValidationException::withMessages([
                'password' => 'La nueva contraseña debe ser distinta a la actual.',
            ]);
        }

        $token = Str::random(64);

        Cache::put(
            self::CACHE_PREFIX.$user->id.':'.$token,
            Crypt::encryptString($newPassword),
            now()->addMinutes(self::EXPIRES_MINUTES),
        );

        $confirmUrl = URL::temporarySignedRoute(
            'shop.account.password.confirm',
            now()->addMinutes(self::EXPIRES_MINUTES),
            [
                'user' => $user->id,
                'token' => $token,
            ],
        );

        try {
            Mail::to($user->email)->send(new ConfirmPasswordChangeMail($user, $confirmUrl));
        } catch (Throwable $e) {
            Cache::forget(self::CACHE_PREFIX.$user->id.':'.$token);
            report($e);

            throw ValidationException::withMessages([
                'password' => 'No pudimos enviar el correo de confirmación. Inténtalo de nuevo en unos minutos.',
            ]);
        }
    }

    public function confirm(User $user, string $token): void
    {
        $cacheKey = self::CACHE_PREFIX.$user->id.':'.$token;
        $payload = Cache::pull($cacheKey);

        if (! is_string($payload) || $payload === '') {
            throw ValidationException::withMessages([
                'password' => 'El enlace de confirmación no es válido o ya expiró. Solicita un nuevo cambio de contraseña.',
            ]);
        }

        try {
            $newPassword = Crypt::decryptString($payload);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'password' => 'El enlace de confirmación no es válido. Solicita un nuevo cambio de contraseña.',
            ]);
        }

        $user->forceFill([
            'password_hash' => $newPassword,
        ])->save();
    }
}
