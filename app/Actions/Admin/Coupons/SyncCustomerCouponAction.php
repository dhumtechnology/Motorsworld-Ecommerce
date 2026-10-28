<?php

namespace App\Actions\Admin\Coupons;

use App\Models\Auth\User;
use App\Models\Coupons\Coupon;
use Illuminate\Validation\ValidationException;

class SyncCustomerCouponAction
{
    public function __construct(
        private readonly NotifyCouponAssignedAction $notifyCouponAssigned,
    ) {}

    public function assign(Coupon $coupon, User $customer): Coupon
    {
        if ($coupon->wasUsedBy((int) $customer->id)) {
            throw ValidationException::withMessages([
                'coupon_id' => 'Este cliente ya usó el cupón '.$coupon->code.'.',
            ]);
        }

        $alreadyAssigned = $coupon->customers()->where('users.id', $customer->id)->exists();

        $coupon->customers()->syncWithoutDetaching([$customer->id]);

        if (! $alreadyAssigned) {
            $this->notifyCouponAssigned->execute($coupon, $customer);
        }

        return $coupon->fresh();
    }

    public function unassign(Coupon $coupon, User $customer): Coupon
    {
        $coupon->customers()->detach($customer->id);

        return $coupon->fresh();
    }
}
