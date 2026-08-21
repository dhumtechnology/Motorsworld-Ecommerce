<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeders de sistema + demo (catálogo, pagos, inventario, pedidos, citas).
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            CatalogSeeder::class,
            PaymentMethodSeeder::class,
            InventoryMovementSeeder::class,
            OrderSalesSeeder::class,
            AppointmentSeeder::class,
        ]);
    }
}
