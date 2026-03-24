<?php

namespace App\Policies;

use App\Models\ClientCode;
use App\Models\User;

class ClientCodePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function detachUser(User $auth, ClientCode $client_code)
    {
        return $auth->roleIsAdmin();
    }
}
