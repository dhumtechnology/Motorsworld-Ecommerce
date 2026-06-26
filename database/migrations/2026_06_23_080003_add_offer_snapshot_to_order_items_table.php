<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_offer_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_offers')
                ->nullOnDelete();

            $table->decimal('list_unit_price', 12, 2)
                ->after('unit_price')
                ->comment('Catalog price before any offer at order time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_offer_id');
            $table->dropColumn('list_unit_price');
        });
    }
};
