<?php

namespace App\Models\Auth\Concerns;

use App\Models\Auth\Role;
use App\Models\Auth\Permission;
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
        $name = $role instanceof Role ? $role->name : $role;

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', $name);
        }

        return $this->roles()->where('name', $name)->exists();
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
        $name = $permission instanceof Permission ? $permission->name : $permission;

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('name', $name))
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

    /**
     * @return Collection<int, Permission>
     */
    public function getAllPermissions(): Collection
    {
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

        return Role::query()->where('name', $role)->firstOrFail();
    }
}
