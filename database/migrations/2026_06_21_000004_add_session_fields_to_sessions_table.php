<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('sessions', 'session_token_hash')) {
                $table->string('session_token_hash', 64)->nullable()->unique()->after('user_id');
            }

            if (! Schema::hasColumn('sessions', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('session_token_hash');
            }

            if (! Schema::hasColumn('sessions', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('created_at');
            }

            if (! Schema::hasColumn('sessions', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('expires_at');
            }

            if (! Schema::hasColumn('sessions', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable()->after('revoked_at');
            }
        });

        $lifetime = (int) config('session.lifetime', 120);

        foreach (DB::table('sessions')->whereNull('session_token_hash')->get() as $session) {
            DB::table('sessions')
                ->where('id', $session->id)
                ->update([
                    'session_token_hash' => hash('sha256', $session->id),
                    'created_at' => now(),
                    'expires_at' => now()->addMinutes($lifetime),
                    'last_used_at' => $session->last_activity
                        ? now()->setTimestamp($session->last_activity)
                        : null,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $columns = ['session_token_hash', 'created_at', 'expires_at', 'revoked_at', 'last_used_at'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sessions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
