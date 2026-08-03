<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('comments');
        });

        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'accepted', 'in_progress', 'attended', 'absent', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('appointments')
            ->where('status', 'accepted')
            ->update(['status' => 'pending']);

        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'in_progress', 'attended', 'absent', 'cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });
    }
};
