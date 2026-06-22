<?php

namespace Database\Seeders;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Seed roles and permissions for the application.
     */
    public function run(): void
    {
        $permissions = collect([
            [
                'name' => 'Acceder al panel admin',
                'slug' => 'admin.view',
                'description' => 'Permite iniciar sesión y acceder al panel administrativo',
            ],
            [
                'name' => 'Gestionar usuarios',
                'slug' => 'users.manage',
                'description' => 'Crear, editar y eliminar usuarios del sistema',
            ],
            [
                'name' => 'Gestionar roles',
                'slug' => 'roles.manage',
                'description' => 'Asignar roles y permisos',
            ],
            [
                'name' => 'Gestionar productos',
                'slug' => 'products.manage',
                'description' => 'Administrar el catálogo de productos',
            ],
            [
                'name' => 'Gestionar pedidos',
                'slug' => 'orders.manage',
                'description' => 'Administrar pedidos de clientes',
            ],
            [
                'name' => 'Acceder a la tienda',
                'slug' => 'shop.access',
                'description' => 'Permite iniciar sesión en el ecommerce',
            ],
            [
                'name' => 'Realizar pedidos',
                'slug' => 'orders.place',
                'description' => 'Comprar productos en la tienda online',
            ],
        ])->map(fn (array $data) => Permission::query()->updateOrCreate(
            ['slug' => $data['slug']],
            $data,
        ));

        $administrador = Role::query()->updateOrCreate(
            ['slug' => 'administrador'],
            [
                'name' => 'Administrador',
                'description' => 'Acceso completo al panel administrativo',
            ],
        );

        $usuario = Role::query()->updateOrCreate(
            ['slug' => 'usuario'],
            [
                'name' => 'Usuario',
                'description' => 'Cliente del ecommerce; no puede acceder al panel admin',
            ],
        );

        $administrador->syncPermissions([
            'Acceder al panel admin',
            'Gestionar usuarios',
            'Gestionar roles',
            'Gestionar productos',
            'Gestionar pedidos',
        ]);

        $usuario->syncPermissions([
            'Acceder a la tienda',
            'Realizar pedidos',
        ]);
    }
}
