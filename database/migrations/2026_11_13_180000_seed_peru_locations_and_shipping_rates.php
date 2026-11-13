<?php

use Database\Seeders\PeruLocationSeeder;
use Database\Seeders\ShippingRateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments') || ! Schema::hasTable('districts') || ! Schema::hasTable('shipping_rates')) {
            return;
        }

        (new PeruLocationSeeder)->run();
        (new ShippingRateSeeder)->run();
    }

    public function down(): void
    {
        // Catálogo geográfico y tarifas por defecto: no se revierte.
    }
};
