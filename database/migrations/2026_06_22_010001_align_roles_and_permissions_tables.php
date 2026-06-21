<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align roles/permissions pivots with the domain schema.
     *
     * Existing: role_user, permission_role, slug columns
     * Target:   user_roles, role_permissions, assigned_at
     */
    public function up(): void
    {
        if (Schema::hasTable('role_user') && ! Schema::hasTable('user_roles')) {
            Schema::rename('role_user', 'user_roles');
        }

        if (Schema::hasTable('permission_role') && ! Schema::hasTable('role_permissions')) {
            Schema::rename('permission_role', 'role_permissions');
        }

        if (Schema::hasTable('user_roles') && ! Schema::hasColumn('user_roles', 'assigned_at')) {
            Schema::table('user_roles', function (Blueprint $table) {
                $table->timestamp('assigned_at')->useCurrent()->after('user_id');
            });
        }

        if (Schema::hasTable('role_permissions') && ! Schema::hasColumn('role_permissions', 'assigned_at')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->timestamp('assigned_at')->useCurrent()->after('role_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('roles', 'slug')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('slug')->unique()->after('name');
            });
        }

        if (! Schema::hasColumn('permissions', 'slug')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('slug')->unique()->after('name');
            });
        }

        if (Schema::hasColumn('user_roles', 'assigned_at')) {
            Schema::table('user_roles', function (Blueprint $table) {
                $table->dropColumn('assigned_at');
            });
        }

        if (Schema::hasColumn('role_permissions', 'assigned_at')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->dropColumn('assigned_at');
            });
        }

        if (Schema::hasTable('user_roles') && ! Schema::hasTable('role_user')) {
            Schema::rename('user_roles', 'role_user');
        }

        if (Schema::hasTable('role_permissions') && ! Schema::hasTable('permission_role')) {
            Schema::rename('role_permissions', 'permission_role');
        }
    }
};
