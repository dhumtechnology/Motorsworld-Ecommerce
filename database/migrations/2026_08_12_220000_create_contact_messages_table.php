<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('document', 20);
            $table->string('phone', 30);
            $table->string('email');
            $table->text('message');

            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
