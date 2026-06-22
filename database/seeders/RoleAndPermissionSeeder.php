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
            ['name' => 'View Admin Panel', 'slug' => 'admin.view', 'description' => 'Access the admin dashboard'],
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'description' => 'Create, update and delete users'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'description' => 'Assign roles and permissions'],
            ['name' => 'Manage Products', 'slug' => 'products.manage', 'description' => 'Manage catalog products'],
            ['name' => 'Manage Orders', 'slug' => 'orders.manage', 'description' => 'Manage customer orders'],
            ['name' => 'Place Orders', 'slug' => 'orders.place', 'description' => 'Place orders in the shop'],
        ])->map(fn (array $data) => Permission::query()->updateOrCreate(
            ['slug' => $data['slug']],
            $data,
        ));

        $admin = Role::query()->updateOrCreate(
            ['slug' => 'administrator'],
            [
                'name' => 'Administrator',
                'description' => 'Full access to the admin panel and system management',
            ],
        );

        $staff = Role::query()->updateOrCreate(
            ['slug' => 'staff'],
            [
                'name' => 'Staff',
                'description' => 'Manages products and orders',
            ],
        );

        $customer = Role::query()->updateOrCreate(
            ['slug' => 'customer'],
            [
                'name' => 'Customer',
                'description' => 'Shop customer with access to the storefront',
            ],
        );

        $admin->syncPermissions($permissions->pluck('name')->all());

        $staff->syncPermissions([
            'Manage Products',
            'Manage Orders',
        ]);

        $customer->syncPermissions([
            'Place Orders',
        ]);
    }
}
