<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('brands')->orderBy('sort_order')->orderBy('id')->pluck('id');

        foreach ($ids as $index => $id) {
            DB::table('brands')->where('id', $id)->update(['sort_order' => $index + 1]);
        }

        Schema::table('brands', function (Blueprint $table) {
            $table->unique('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique(['sort_order']);
        });
    }
};
