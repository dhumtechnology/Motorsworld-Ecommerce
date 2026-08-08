<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('exchange_rate_buy', 12, 4)->nullable()->after('currency');
            $table->decimal('exchange_rate_sell', 12, 4)->nullable()->after('exchange_rate_buy');
            $table->date('exchange_rate_date')->nullable()->after('exchange_rate_sell');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate_buy', 'exchange_rate_sell', 'exchange_rate_date']);
        });
    }
};
