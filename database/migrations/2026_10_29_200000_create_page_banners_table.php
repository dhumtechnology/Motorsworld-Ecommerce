<?php

use App\Enums\Content\PageBannerKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_banners', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        $now = now();

        foreach (PageBannerKey::cases() as $key) {
            DB::table('page_banners')->insert([
                'page_key' => $key->value,
                'image' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('page_banners');
    }
};
