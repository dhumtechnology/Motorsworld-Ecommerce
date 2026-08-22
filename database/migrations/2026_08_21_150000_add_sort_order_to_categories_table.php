<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('image');
        });

        $motosId = DB::table('categories')
            ->whereRaw('UPPER(name) = ?', ['MOTOCICLETAS'])
            ->value('id');

        $otherIds = DB::table('categories')
            ->when($motosId !== null, fn ($query) => $query->where('id', '!=', $motosId))
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('id');

        $orderedIds = collect($motosId !== null ? [$motosId] : [])
            ->merge($otherIds)
            ->values();

        foreach ($orderedIds as $index => $id) {
            DB::table('categories')->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
