<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Completa lo que falló en 2026_08_07_240000 (unique de cart_items / FK order_items).

        if (Schema::hasColumn('cart_items', 'product_variant_id')) {
            $hasCartVariantUnique = collect(DB::select('SHOW INDEX FROM cart_items'))
                ->contains(fn ($idx) => $idx->Key_name === 'cart_items_cart_id_product_variant_id_unique');

            if (! $hasCartVariantUnique) {
                Schema::table('cart_items', function (Blueprint $table) {
                    // En MySQL el unique (cart_id, product_id) está ligado al FK de cart_id.
                    $table->dropForeign(['cart_id']);
                    $table->dropForeign(['product_id']);
                });

                Schema::table('cart_items', function (Blueprint $table) {
                    $table->dropUnique(['cart_id', 'product_id']);
                });

                Schema::table('cart_items', function (Blueprint $table) {
                    $table->foreign('cart_id')->references('id')->on('carts')->cascadeOnDelete();
                    $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
                    $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
                    $table->unique(['cart_id', 'product_variant_id']);
                });
            }
        }

        $orderItemHasFk = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'order_items'
               AND COLUMN_NAME = 'product_variant_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL"
        ))->isNotEmpty();

        if (Schema::hasColumn('order_items', 'product_variant_id') && ! $orderItemHasFk) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
                $table->index('product_variant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropUnique(['cart_id', 'product_variant_id']);
            $table->dropForeign(['cart_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreign('cart_id')->references('id')->on('carts')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->unique(['cart_id', 'product_id']);
        });
    }
};
