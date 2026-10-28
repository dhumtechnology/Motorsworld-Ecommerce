<?php

namespace App\Actions\Admin\ShippingRates;

use App\Models\Shipping\ShippingRate;
use Illuminate\Support\Facades\DB;

class UpsertShippingRateAction
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $districtIds
     */
    public function execute(array $attributes, array $districtIds = [], ?ShippingRate $shippingRate = null): ShippingRate
    {
        return DB::transaction(function () use ($attributes, $districtIds, $shippingRate) {
            if ($shippingRate === null) {
                $shippingRate = ShippingRate::query()->create($attributes);
            } else {
                $shippingRate->update($attributes);
            }

            if (! empty($attributes['is_fallback'])) {
                ShippingRate::query()
                    ->whereKeyNot($shippingRate->id)
                    ->where('is_fallback', true)
                    ->update(['is_fallback' => false]);
            }

            $shippingRate->districts()->sync($districtIds);

            return $shippingRate->fresh(['districts']);
        });
    }
}
