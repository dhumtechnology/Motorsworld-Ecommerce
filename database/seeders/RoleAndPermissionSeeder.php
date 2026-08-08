<?php

namespace Database\Seeders;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Support\Auth\PermissionCatalog;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Seed permissions and the administrator role.
     */
    public function run(): void
    {
        $permissions = collect(PermissionCatalog::all())
            ->map(function (array $data) {
                $permission = Permission::query()
                    ->where('slug', $data['slug'])
                    ->orWhere('name', $data['name'])
                    ->first();

                if ($permission === null) {
                    return Permission::query()->create($data);
                }

                $permission->update($data);

                return $permission->fresh();
            });

        Permission::query()
            ->whereNotIn('slug', PermissionCatalog::allSlugs())
            ->delete();

        $bySlug = $permissions->keyBy('slug');

        $administrador = Role::query()->updateOrCreate(
            ['slug' => 'administrador'],
            [
                'name' => 'Administrador',
                'description' => 'Acceso completo al panel administrativo',
            ],
        );

        // Rol de cliente de tienda (requerido por el registro / checkout).
        $usuario = Role::query()->updateOrCreate(
            ['slug' => 'usuario'],
            [
                'name' => 'Usuario',
                'description' => 'Cliente del ecommerce; no puede acceder al panel admin',
            ],
        );

        $administrador->syncPermissions(
            $bySlug->only(PermissionCatalog::adminCrudSlugs())->all(),
        );

        $usuario->syncPermissions(
            $bySlug->only(['shop.access', 'orders.place'])->all(),
        );
    }
}
