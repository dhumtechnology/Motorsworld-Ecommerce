<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->char('iso2', 2);
            $table->char('iso3', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('iso2');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('ubigeo', 2);
            $table->timestamps();

            $table->unique(['country_id', 'ubigeo']);
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('ubigeo', 4);
            $table->timestamps();

            $table->unique(['department_id', 'ubigeo']);
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('ubigeo', 6);
            $table->timestamps();

            $table->unique(['province_id', 'ubigeo']);
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('country')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('province_id')->constrained()->nullOnDelete();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('PEN');
            $table->boolean('is_fallback')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_rate_district', function (Blueprint $table) {
            $table->foreignId('shipping_rate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->primary(['shipping_rate_id', 'district_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_amount', 12, 2)->default(0)->after('discount_amount');
            $table->foreignId('shipping_rate_id')->nullable()->after('shipping_amount')->constrained()->nullOnDelete();
        });

        Schema::create('coupon_department', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'department_id']);
        });

        Schema::create('coupon_province', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'province_id']);
        });

        Schema::create('coupon_district', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'district_id']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('cities');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->json('cities')->nullable();
        });

        Schema::dropIfExists('coupon_district');
        Schema::dropIfExists('coupon_province');
        Schema::dropIfExists('coupon_department');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_rate_id');
            $table->dropColumn('shipping_amount');
        });

        Schema::dropIfExists('shipping_rate_district');
        Schema::dropIfExists('shipping_rates');

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('province_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::dropIfExists('districts');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('countries');
    }
};
