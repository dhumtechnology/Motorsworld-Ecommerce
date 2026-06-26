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
        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->default('')->after('sku');
        });

        \Illuminate\Support\Facades\DB::table('products')
            ->where('name', '')
            ->update(['name' => \Illuminate\Support\Facades\DB::raw('sku')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
