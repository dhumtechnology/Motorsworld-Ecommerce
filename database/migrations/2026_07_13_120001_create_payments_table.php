<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('culqi');
            $table->enum('method', ['card', 'yape', 'plin', 'pagoefectivo']);
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'refunded'])
                ->default('pending');
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('PEN');
            $table->string('culqi_charge_id', 64)->nullable()->unique();
            $table->string('culqi_order_id', 64)->nullable()->unique();
            $table->string('payment_code', 64)->nullable();
            $table->text('qr_url')->nullable();
            $table->text('payment_url')->nullable();
            $table->string('source_id', 64)->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
