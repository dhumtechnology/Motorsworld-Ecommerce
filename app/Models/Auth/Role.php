<?php

namespace App\Models\Auth;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('assigned_at');
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot('assigned_at');
    }

    public function givePermissionTo(Permission|string $permission): static
    {
        $permission = $this->resolvePermission($permission);

        $this->permissions()->syncWithoutDetaching([
            $permission->id => ['assigned_at' => now()],
        ]);

        return $this;
    }

    public function syncPermissions(Permission|string|array $permissions): static
    {
        $permissionData = collect($permissions)
            ->mapWithKeys(fn (Permission|string $permission) => [
                $this->resolvePermission($permission)->id => ['assigned_at' => now()],
            ])
            ->all();

        $this->permissions()->sync($permissionData);

        return $this;
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $name = $permission instanceof Permission ? $permission->name : $permission;

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('name', $name);
        }

        return $this->permissions()->where('name', $name)->exists();
    }

    protected function resolvePermission(Permission|string $permission): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        return Permission::query()->where('name', $permission)->firstOrFail();
    }
}
