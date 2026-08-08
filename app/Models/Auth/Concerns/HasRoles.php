<?php

namespace App\Models\Auth\Concerns;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('assigned_at');
    }

    public function assignRole(Role|string $role): static
    {
        $role = $this->resolveRole($role);

        $this->roles()->syncWithoutDetaching([
            $role->id => ['assigned_at' => now()],
        ]);

        return $this;
    }

    public function syncRoles(Role|string|array $roles): static
    {
        $roleData = collect($roles)
            ->mapWithKeys(fn (Role|string $role) => [
                $this->resolveRole($role)->id => ['assigned_at' => now()],
            ])
            ->all();

        $this->roles()->sync($roleData);

        return $this;
    }

    public function removeRole(Role|string $role): static
    {
        $role = $this->resolveRole($role);

        $this->roles()->detach($role->id);

        return $this;
    }

    public function hasRole(Role|string $role): bool
    {
        $value = $role instanceof Role ? $role->name : $role;

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $item) => $item->name === $value || $item->slug === $value);
        }

        return $this->roles()
            ->where(fn ($query) => $query->where('name', $value)->orWhere('slug', $value))
            ->exists();
    }

    /**
     * @param  list<Role|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<Role|string>  $roles
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $value = $permission instanceof Permission ? $permission->slug : $permission;

        if ($this->relationLoaded('roles')) {
            foreach ($this->roles as $role) {
                if (! $role->relationLoaded('permissions')) {
                    $role->load('permissions');
                }

                if ($role->permissions->contains(
                    fn (Permission $item) => $item->slug === $value || $item->name === $value,
                )) {
                    return true;
                }
            }

            return false;
        }

        return $this->roles()
            ->whereHas(
                'permissions',
                fn ($query) => $query->where('slug', $value)->orWhere('name', $value),
            )
            ->exists();
    }

    /**
     * @param  list<Permission|string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessAdmin(): bool
    {
        return $this->hasPermission('admin.access');
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getAllPermissions(): Collection
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles
                ->flatMap(function (Role $role) {
                    if (! $role->relationLoaded('permissions')) {
                        $role->load('permissions');
                    }

                    return $role->permissions;
                })
                ->unique('id')
                ->values();
        }

        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->values();
    }

    protected function resolveRole(Role|string $role): Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        return Role::query()
            ->where('name', $role)
            ->orWhere('slug', $role)
            ->firstOrFail();
    }
}
