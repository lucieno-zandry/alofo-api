<?php

namespace App\Policies;

use App\Models\Image;
use App\Models\User;

class ImagePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function store(User $user)
    {
        return !!$user;
    }

    public function update(User $user, Image $image)
    {
        return !!$user;
    }
}
