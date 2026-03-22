<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function canUpdateStatus(User $auth, User $user): bool
    {
        return $auth->roleIsAdmin();
    }
}
