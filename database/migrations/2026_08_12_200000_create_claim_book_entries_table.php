<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_book_entries', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('document', 20);
            $table->string('address');
            $table->string('phone', 30);
            $table->string('email');

            $table->string('good_type', 20);
            $table->text('good_description');
            $table->decimal('claimed_amount', 12, 2)->nullable();

            $table->string('claim_type', 20);
            $table->text('detail');
            $table->text('consumer_request');

            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index(['claim_type', 'status']);
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_book_entries');
    }
};
