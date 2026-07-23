<?php

namespace App\Actions\Admin\Users;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;

class UpsertAdminUserAction
{
    /**
     * @param  array{email: string, status: UserStatus, password?: string|null}  $attributes
     */
    public function execute(array $attributes, ?User $user = null): User
    {
        return DB::transaction(function () use ($attributes, $user) {
            $payload = [
                'email' => $attributes['email'],
                'status' => $attributes['status'],
            ];

            if (! empty($attributes['password'])) {
                $payload['password_hash'] = $attributes['password'];
            }

            if ($user === null) {
                $user = User::query()->create([
                    ...$payload,
                    'email_verified_at' => now(),
                ]);

                $user->assignRole('Administrador');
            } else {
                $user->update($payload);
                $user->syncRoles(['Administrador']);
            }

            return $user->fresh(['roles']);
        });
    }
}
