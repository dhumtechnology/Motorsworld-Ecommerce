<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeders de sistema (roles, permisos, admin, categorías, marcas).
     * Demo (productos, pedidos, citas, inventario, métodos de pago) queda en pausa.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            CatalogSeeder::class,
            // PaymentMethodSeeder::class,
            // InventoryMovementSeeder::class,
            // OrderSalesSeeder::class,
            // AppointmentSeeder::class,
        ]);
    }
}
