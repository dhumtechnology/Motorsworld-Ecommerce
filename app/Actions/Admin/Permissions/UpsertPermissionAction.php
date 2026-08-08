<?php

namespace App\Actions\Admin\Permissions;

use App\Models\Auth\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpsertPermissionAction
{
    /**
     * @param  array{name: string, slug?: string|null, description?: string|null}  $attributes
     */
    public function execute(array $attributes, ?Permission $permission = null): Permission
    {
        return DB::transaction(function () use ($attributes, $permission) {
            $slug = filled($attributes['slug'] ?? null)
                ? strtolower(trim((string) $attributes['slug']))
                : Str::slug($attributes['name'], '.');

            $payload = [
                'name' => $attributes['name'],
                'slug' => $slug,
                'description' => $attributes['description'] ?? null,
            ];

            if ($permission === null) {
                $permission = Permission::query()->create($payload);
            } else {
                $permission->update($payload);
            }

            return $permission->fresh();
        });
    }
}
