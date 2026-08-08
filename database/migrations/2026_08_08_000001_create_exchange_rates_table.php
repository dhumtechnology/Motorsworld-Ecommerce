<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date');
            $table->decimal('buy_price', 12, 4);
            $table->decimal('sell_price', 12, 4);
            $table->char('base_currency', 3)->default('USD');
            $table->char('quote_currency', 3)->default('PEN');
            $table->string('source', 50)->default('decolecta');
            $table->json('payload')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['rate_date', 'base_currency', 'quote_currency'], 'exchange_rates_unique_day');
            $table->index('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
