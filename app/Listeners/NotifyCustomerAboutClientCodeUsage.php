<?php

namespace App\Listeners;

use App\Events\ClientCodeUsed;
use App\Notifications\Customer\ClientCodeAttached;
use App\Notifications\Customer\ClientCodeDetached;

class NotifyCustomerAboutClientCodeUsage
{
    public function handle(ClientCodeUsed $event): void
    {
        /** @var \App\Models\User */
        $user = $event->user;
        
        if (!$user->canUseNotifications()) return;

        if ($event->action === 'attach') {
            $user->notify(new ClientCodeAttached($user, $event->client_code));
        } else {
            $user->notify(new ClientCodeDetached($user, $event->client_code));
        }
    }
}
