<?php

namespace App\Listeners;

use App\Events\ClientCodeUsed;
use App\Notifications\Customer\ClientCodeAttached;
use App\Notifications\Customer\ClientCodeDetached;

class NotifyCustomerAboutClientCodeUsage
{
    public function handle(ClientCodeUsed $event): void
    {
        if ($event->action === 'attach') {
            $event->user->notify(new ClientCodeAttached($event->user, $event->client_code));
        } else {
            $event->user->notify(new ClientCodeDetached($event->user, $event->client_code));
        }
    }
}
