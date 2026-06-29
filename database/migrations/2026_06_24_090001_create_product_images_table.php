<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'path']);
            $table->index(['product_id', 'sort_order']);
        });

        $now = now();

        foreach (
            DB::table('products')
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->orderBy('id')
                ->cursor() as $product
        ) {
            DB::table('product_images')->insert([
                'product_id' => $product->id,
                'path' => $product->image,
                'sort_order' => 1,
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
