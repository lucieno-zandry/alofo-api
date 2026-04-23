<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LandingBlock;

class LandingBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->roleIsAdmin();
    }

    public function view(User $user, LandingBlock $landingBlock): bool
    {
        return $user->roleIsAdmin();
    }

    public function create(User $user): bool
    {
        return $user->roleIsAdmin();
    }

    public function update(User $user, LandingBlock $landingBlock): bool
    {
        return $user->roleIsAdmin();
    }

        /**
     * Determine whether the user can update the model.
     */
    public function updateAny(User $user): bool
    {
        return $user->roleIsAdmin();
    }


    public function delete(User $user, LandingBlock $landingBlock): bool
    {
        return $user->roleIsAdmin();
    }
}
