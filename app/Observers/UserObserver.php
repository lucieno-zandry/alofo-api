<?php

// app/Observers/UserObserver.php
namespace App\Observers;

use App\Models\User;
use App\Models\UserStatus;


class UserObserver
{
    public function created(User $user): void
    {
        $status = $user->roleIsCustomer() ? 'approved' : 'pending';

        UserStatus::create([
            'user_id' => $user->id,
            'status'  => $status,
            'set_by'  => auth('sanctum')->id(), // or null
        ]);
    }
}
