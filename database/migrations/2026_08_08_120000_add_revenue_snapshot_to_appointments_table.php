<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('charged_amount', 12, 2)->nullable()->after('status');
            $table->char('charged_currency', 3)->nullable()->after('charged_amount');
            $table->decimal('exchange_rate_buy', 12, 4)->nullable()->after('charged_currency');
            $table->decimal('exchange_rate_sell', 12, 4)->nullable()->after('exchange_rate_buy');
            $table->date('exchange_rate_date')->nullable()->after('exchange_rate_sell');
            $table->timestamp('attended_at')->nullable()->after('exchange_rate_date');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'charged_amount',
                'charged_currency',
                'exchange_rate_buy',
                'exchange_rate_sell',
                'exchange_rate_date',
                'attended_at',
            ]);
        });
    }
};
