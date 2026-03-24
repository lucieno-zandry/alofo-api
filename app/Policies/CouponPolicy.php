<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function create(User $user)
    {
        return $user->roleIsAdmin();
    }

    public function update(User $user, Coupon $coupon)
    {
        return $user->roleIsAdmin();
    }

    public function destroy(User $user)
    {
        return $user->roleIsAdmin();
    }
}
