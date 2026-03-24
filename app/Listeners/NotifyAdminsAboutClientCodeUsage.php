<?php

namespace App\Listeners;

use App\Events\ClientCodeUsed;
use App\Models\User;
use App\Notifications\Admin\ClientCodeAttached;
use App\Notifications\Admin\ClientCodeDetached;

class NotifyAdminsAboutClientCodeUsage
{
    public function handle(ClientCodeUsed $event): void
    {
        $admins = User::whereIn('role', ['admin', 'manager'])->get();

        if ($event->action === 'attach') {
            foreach ($admins as $admin) {
                $admin->notify(new ClientCodeAttached($event->user, $event->client_code, $event->performedBy ?? null));
            }
        } else {
            foreach ($admins as $admin) {
                $admin->notify(new ClientCodeDetached($event->user, $event->client_code, $event->performedBy ?? null));
            }
        }
    }
}
