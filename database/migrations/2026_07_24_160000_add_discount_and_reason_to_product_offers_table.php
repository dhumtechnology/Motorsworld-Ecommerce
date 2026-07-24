<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->nullable()->after('offer_price_amount');
            $table->string('reason', 500)->nullable()->after('discount_percent');
        });

        // Backfill percent from list price when possible.
        $offers = DB::table('product_offers')
            ->join('products', 'products.id', '=', 'product_offers.product_id')
            ->select(
                'product_offers.id',
                'product_offers.offer_price_amount',
                'products.price_amount',
            )
            ->get();

        foreach ($offers as $offer) {
            $list = (float) $offer->price_amount;
            $sale = (float) $offer->offer_price_amount;

            if ($list <= 0 || $sale >= $list) {
                continue;
            }

            $percent = round((($list - $sale) / $list) * 100, 2);

            DB::table('product_offers')
                ->where('id', $offer->id)
                ->update([
                    'discount_percent' => $percent,
                    'reason' => 'Oferta promocional',
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'reason']);
        });
    }
};
