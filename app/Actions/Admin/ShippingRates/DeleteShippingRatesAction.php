<?php

namespace App\Actions\Admin\ShippingRates;

use App\Models\Shipping\ShippingRate;
use Illuminate\Support\Facades\DB;

class DeleteShippingRatesAction
{
    /**
     * @param  list<int>  $ids
     * @return array{deleted: int, blocked: list<string>}
     */
    public function execute(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return ['deleted' => 0, 'blocked' => []];
        }

        return DB::transaction(function () use ($ids) {
            $deleted = ShippingRate::query()->whereIn('id', $ids)->delete();

            return [
                'deleted' => $deleted,
                'blocked' => [],
            ];
        });
    }
}
