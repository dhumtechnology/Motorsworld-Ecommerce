<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Por ahora solo roles + un admin. El resto de seeders queda
     * comentado para reactivarlos cuando haga falta datos demo.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            // CatalogSeeder::class,
            // PaymentMethodSeeder::class,
            // InventoryMovementSeeder::class,
            // OrderSalesSeeder::class,
            // ProductReviewSeeder::class,
            // AppointmentSeeder::class,
        ]);
    }
}
