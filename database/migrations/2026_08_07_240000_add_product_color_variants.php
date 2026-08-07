<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('hex', 7)->nullable();
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_id');
        });

        Schema::create('color_product_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_variant_id', 'color_id']);
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id');
        });

        $this->migrateExistingProductsToVariants();

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropUnique(['product_id']);
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            $table->unique('product_variant_id');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->index('product_variant_id');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->index('product_variant_id');
        });

        Schema::table('cart_items', function (Blueprint $table) {
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

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_variant_id']);
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropUnique(['product_variant_id']);
            $table->dropColumn('product_variant_id');
            $table->unique('product_id');
        });

        Schema::dropIfExists('color_product_variant');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('colors');
    }

    private function migrateExistingProductsToVariants(): void
    {
        $products = DB::table('products')->select(['id', 'sku'])->orderBy('id')->get();

        foreach ($products as $product) {
            $variantSku = $product->sku.'-STD';
            $variantId = DB::table('product_variants')->insertGetId([
                'product_id' => $product->id,
                'sku' => $variantSku,
                'name' => 'Estándar',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory')
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->update(['product_variant_id' => $variantId]);

            if (! DB::table('inventory')->where('product_variant_id', $variantId)->exists()) {
                DB::table('inventory')->insert([
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'total_stock' => 0,
                    'available_stock' => 0,
                    'reserved_stock' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('product_images')
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->update(['product_variant_id' => $variantId]);

            DB::table('inventory_movements')
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->update(['product_variant_id' => $variantId]);

            DB::table('cart_items')
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->update(['product_variant_id' => $variantId]);

            DB::table('order_items')
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->update(['product_variant_id' => $variantId]);
        }
    }
};
