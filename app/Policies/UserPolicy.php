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

    public function updateStatus(User $auth, User $user): bool
    {
        return $auth->roleIsAdmin();
    }

    public function update(User $auth, User $user): bool
    {
        return $auth->roleIsAdmin();
    }
}
