<?php

namespace App\Actions\Admin\Coupons;

use App\Models\Coupons\Coupon;

class GetCouponDetailsAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Coupon $coupon): array
    {
        $coupon->load([
            'customers.customerProfile',
            'products:id,sku,name',
            'categories:id,name',
            'brands:id,name',
        ]);

        $usages = $coupon->usages()
            ->with([
                'user.customerProfile',
                'order:id,user_id,total_amount,currency,created_at,status',
            ])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return [
            'coupon' => $coupon,
            'usages' => $usages,
            'stats' => [
                'customers_count' => $coupon->customers->count(),
                'usages_count' => $coupon->usages()->count(),
                'products_count' => $coupon->products->count(),
                'categories_count' => $coupon->categories->count(),
                'brands_count' => $coupon->brands->count(),
            ],
        ];
    }
}
