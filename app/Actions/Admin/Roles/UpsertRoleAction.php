<?php

namespace App\Actions\Admin\Roles;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpsertRoleAction
{
    /**
     * @param  array{name: string, description?: string|null, permission_ids?: list<int>}  $attributes
     */
    public function execute(array $attributes, ?Role $role = null): Role
    {
        return DB::transaction(function () use ($attributes, $role) {
            $payload = [
                'name' => $attributes['name'],
                'slug' => Str::slug($attributes['name']),
                'description' => $attributes['description'] ?? null,
            ];

            if ($role === null) {
                $role = Role::query()->create($payload);
            } else {
                if ($role->isSystem()) {
                    unset($payload['slug']);
                    $payload['name'] = $role->name;
                }

                $role->update($payload);
            }

            $permissionIds = array_values(array_unique(array_map('intval', $attributes['permission_ids'] ?? [])));

            $permissions = $permissionIds === []
                ? []
                : Permission::query()->whereIn('id', $permissionIds)->get()->all();

            $role->syncPermissions($permissions);

            return $role->fresh(['permissions']);
        });
    }
}
