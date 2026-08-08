<?php

namespace App\Actions\Admin\Users;

use App\Enums\Auth\UserStatus;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertAdminUserAction
{
    /**
     * @param  array{email: string, status: UserStatus, password?: string|null, role_ids: list<int>}  $attributes
     */
    public function execute(array $attributes, ?User $user = null): User
    {
        return DB::transaction(function () use ($attributes, $user) {
            $roles = Role::query()
                ->whereIn('id', $attributes['role_ids'])
                ->with('permissions')
                ->get();

            if ($roles->isEmpty()) {
                throw ValidationException::withMessages([
                    'role_ids' => 'Debes asignar al menos un rol.',
                ]);
            }

            $hasAdminAccess = $roles->contains(
                fn (Role $role) => $role->permissions->contains('slug', 'admin.access'),
            );

            if (! $hasAdminAccess) {
                throw ValidationException::withMessages([
                    'role_ids' => 'Al menos un rol debe incluir el permiso de acceso al panel admin.',
                ]);
            }

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
            } else {
                $user->update($payload);
            }

            $user->syncRoles($roles->all());

            return $user->fresh(['roles']);
        });
    }
}
