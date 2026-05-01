<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\ClientCodeUsed;
use App\Models\User;
use App\Notifications\Admin\ClientCodeAttached;
use App\Notifications\Admin\ClientCodeDetached;
use App\Services\AdminService;

class NotifyAdminsAboutClientCodeUsage
{
    public function handle(ClientCodeUsed $event): void
    {
        $admins = app(AdminService::class);

        if ($event->action === 'attach') {
            $admins->notify(new ClientCodeAttached($event->user, $event->client_code, $event->performedBy ?? null));
        } else {
            $admins->notify(new ClientCodeDetached($event->user, $event->client_code, $event->performedBy ?? null));
        }
    }
}
