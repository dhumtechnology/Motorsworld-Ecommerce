<?php

namespace App\Actions\Admin\Offers;

use App\Models\Products\ProductOffer;
use Illuminate\Support\Facades\DB;

class UpsertProductOfferAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, ?ProductOffer $offer = null): ProductOffer
    {
        return DB::transaction(function () use ($attributes, $offer) {
            if ($offer === null) {
                $offer = ProductOffer::query()->create($attributes);
            } else {
                $offer->update($attributes);
            }

            return $offer->fresh(['product']);
        });
    }
}
