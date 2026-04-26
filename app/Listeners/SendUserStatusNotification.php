<?php

namespace App\Listeners;

use App\Events\UserStatusUpdatedEvent;
use App\Notifications\UserStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendUserStatusNotification implements ShouldQueue
{
    public function handle(UserStatusUpdatedEvent $event)
    {
        /** @var \App\Models\User */
        $user = $event->user;

        if (!$user->canUseNotifications()) return;
        
        $status = $event->userStatus;

        $message = "Your account status has been {$status->status}.";

        $notification = new UserStatusChangedNotification(
            $status->status,
            $message,
            $status->reason
        );

        // Assumes User model uses Notifiable trait
        $user->notify($notification);
    }
}
