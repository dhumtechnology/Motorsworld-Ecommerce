<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16);
            $table->string('name');
            $table->enum('type', ['percent', 'amount']);
            $table->decimal('value', 12, 2);
            $table->char('currency', 3)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('min_products')->nullable();
            $table->decimal('min_subtotal', 12, 2)->nullable();
            $table->char('min_subtotal_currency', 3)->nullable();
            $table->json('payment_methods')->nullable();
            $table->json('cities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code');
        });

        Schema::create('coupon_user', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'user_id']);
        });

        Schema::create('coupon_product', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'product_id']);
        });

        Schema::create('coupon_category', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'category_id']);
        });

        Schema::create('coupon_brand', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'brand_id']);
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_amount', 12, 2);
            $table->char('currency', 3);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['coupon_id', 'user_id']);
            $table->unique('order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 12, 2)->nullable()->after('payment_status');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal_amount');
            $table->foreignId('coupon_id')->nullable()->after('currency')->constrained()->nullOnDelete();
            $table->string('coupon_code', 16)->nullable()->after('coupon_id');
        });

        DB::table('orders')->whereNull('subtotal_amount')->update([
            'subtotal_amount' => DB::raw('total_amount'),
            'discount_amount' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn([
                'subtotal_amount',
                'discount_amount',
                'coupon_code',
            ]);
        });

        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupon_brand');
        Schema::dropIfExists('coupon_category');
        Schema::dropIfExists('coupon_product');
        Schema::dropIfExists('coupon_user');
        Schema::dropIfExists('coupons');
    }
};
