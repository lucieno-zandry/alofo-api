<?php

namespace App\Events;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusUpdatedEvent
{
    use Dispatchable, SerializesModels;

    public User $user;
    public UserStatus $userStatus;

    public function __construct(User $user, UserStatus $userStatus)
    {
        $this->user = $user;
        $this->userStatus = $userStatus;
    }
}