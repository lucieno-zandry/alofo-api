<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
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

    public function update(User $user, Promotion $promotion)
    {
        return $user->roleIsAdmin();
    }

    public function destroy(User $user)
    {
        return $user->roleIsAdmin();
    }

    public function attachVariant(User $user)
    {
        return $user->roleIsAdmin();
    }

    public function detachVariant(User $user)
    {
        return $user->roleIsAdmin();
    }
}
