<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'paypal_order_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('paypal_order_id', 64)->nullable()->unique()->after('mp_payment_id');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('card', 'yape', 'paypal', 'plin', 'pagoefectivo') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('card', 'yape', 'plin', 'pagoefectivo') NOT NULL");
        }

        if (Schema::hasColumn('payments', 'paypal_order_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique(['paypal_order_id']);
                $table->dropColumn('paypal_order_id');
            });
        }
    }
};
