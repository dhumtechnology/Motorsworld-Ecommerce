<?php

namespace App\Actions\Admin\Coupons;

use App\Models\Coupons\Coupon;
use Illuminate\Support\Facades\DB;

class DeleteCouponsAction
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
            $coupons = Coupon::query()
                ->withCount('usages')
                ->whereIn('id', $ids)
                ->get();

            $blocked = [];
            $deletableIds = [];

            foreach ($coupons as $coupon) {
                if ((int) $coupon->usages_count > 0) {
                    $blocked[] = $coupon->code;

                    continue;
                }

                $deletableIds[] = $coupon->id;
            }

            $deleted = 0;

            if ($deletableIds !== []) {
                $deleted = Coupon::query()->whereIn('id', $deletableIds)->delete();
            }

            return [
                'deleted' => (int) $deleted,
                'blocked' => $blocked,
            ];
        });
    }
}
