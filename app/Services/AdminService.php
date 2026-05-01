<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AdminService
{
    function notify($notification)
    {
        /** @var Collection */
        $admins = User::where('role', UserRole::ADMIN->value)->get();
        $admins->each(function ($admin) use ($notification) {
            if (!$admin->hasBeenApproved()) return;
            $admin->notify($notification);
        });
    }
}
