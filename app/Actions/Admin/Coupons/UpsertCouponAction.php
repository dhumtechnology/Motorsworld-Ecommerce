<?php

namespace App\Actions\Admin\Coupons;

use App\Models\Auth\User;
use App\Models\Coupons\Coupon;
use Illuminate\Support\Facades\DB;

class UpsertCouponAction
{
    public function __construct(
        private readonly NotifyCouponAssignedAction $notifyCouponAssigned,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array{customer_ids?: list<int>, product_ids?: list<int>, category_ids?: list<int>, brand_ids?: list<int>}  $relations
     */
    public function execute(array $attributes, array $relations = [], ?Coupon $coupon = null): Coupon
    {
        $previousCustomerIds = $coupon === null
            ? []
            : $coupon->customers()->pluck('users.id')->all();

        $coupon = DB::transaction(function () use ($attributes, $relations, $coupon) {
            if ($coupon === null) {
                $coupon = Coupon::query()->create($attributes);
            } else {
                $coupon->update($attributes);
            }

            $coupon->customers()->sync($relations['customer_ids'] ?? []);
            $coupon->products()->sync($relations['product_ids'] ?? []);
            $coupon->categories()->sync($relations['category_ids'] ?? []);
            $coupon->brands()->sync($relations['brand_ids'] ?? []);

            return $coupon->fresh([
                'customers.customerProfile',
                'products:id,sku,name',
                'categories:id,name',
                'brands:id,name',
            ]);
        });

        $newCustomerIds = array_values(array_diff(
            array_map('intval', $relations['customer_ids'] ?? []),
            array_map('intval', $previousCustomerIds),
        ));

        if ($newCustomerIds !== []) {
            $customers = User::query()
                ->with('customerProfile')
                ->whereIn('id', $newCustomerIds)
                ->get();

            $this->notifyCouponAssigned->executeMany($coupon, $customers);
        }

        return $coupon;
    }
}
