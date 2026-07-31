<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class, // roles/permisos necesarios para el admin
            UserSeeder::class, // solo superusuario (admin@motosworld.test)
            // CatalogSeeder::class,
            // PaymentMethodSeeder::class,
            // InventoryMovementSeeder::class,
            // OrderSalesSeeder::class,
            // AppointmentSeeder::class,
        ]);
    }
}
