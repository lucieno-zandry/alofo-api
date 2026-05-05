<?php

namespace App\Listeners;

use App\Events\UserEmailVerified;
use App\Notifications\Admin\NewUserRegistered;
use App\Services\AdminService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyAdminNewVerifiedUser
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserEmailVerified $event): void
    {
        $admins = app(AdminService::class);
        $admins->notify(new NewUserRegistered($event->user));
    }
}
