<?php

namespace App\Actions\Admin\Coupons;

use App\Mail\CouponAssignedMail;
use App\Models\Auth\User;
use App\Models\Coupons\Coupon;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotifyCouponAssignedAction
{
    /**
     * @param  iterable<User>  $customers
     */
    public function executeMany(Coupon $coupon, iterable $customers): void
    {
        foreach ($customers as $customer) {
            $this->execute($coupon, $customer);
        }
    }

    public function execute(Coupon $coupon, User $customer): void
    {
        $email = $customer->email;

        if (! filled($email)) {
            return;
        }

        $customer->loadMissing('customerProfile');

        try {
            Mail::to($email)->send(new CouponAssignedMail($coupon, $customer));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
