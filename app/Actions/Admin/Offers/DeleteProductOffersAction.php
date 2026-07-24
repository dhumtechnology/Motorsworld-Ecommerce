<?php

namespace App\Actions\Admin\Offers;

use App\Models\Products\ProductOffer;
use Illuminate\Support\Facades\DB;

class DeleteProductOffersAction
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
            $deleted = ProductOffer::query()->whereIn('id', $ids)->delete();

            return [
                'deleted' => (int) $deleted,
                'blocked' => [],
            ];
        });
    }
}
