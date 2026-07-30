<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_type_id')->constrained('service_types')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->char('currency', 3)->default('PEN');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['service_type_id', 'is_active']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE appointments MODIFY user_id BIGINT UNSIGNED NULL');

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();

            $table->string('customer_name')->nullable()->after('user_id');
            $table->string('customer_document', 20)->nullable()->after('customer_name');
            $table->string('customer_phone', 30)->nullable()->after('customer_document');
            $table->string('customer_email')->nullable()->after('customer_phone');

            $table->foreignId('brand_id')
                ->nullable()
                ->after('appointment_at')
                ->constrained('brands')
                ->nullOnDelete();

            $table->foreignId('service_package_id')
                ->nullable()
                ->after('service_type_id')
                ->constrained('service_packages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_package_id');
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn([
                'customer_name',
                'customer_document',
                'customer_phone',
                'customer_email',
            ]);
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE appointments MODIFY user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::dropIfExists('service_packages');
    }
};
