<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('appointment_at');
            $table->foreignId('vehicle_model_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('km', 10, 2)->nullable();
            $table->string('plate')->nullable();
            $table->foreignId('service_type_id')->constrained()->restrictOnDelete();
            $table->text('comments')->nullable();
            $table->enum('status', [
                'pending',
                'in_progress',
                'attended',
                'absent',
                'cancelled',
            ])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
