<?php

namespace App\Listeners;

use App\Events\NewCollaboratorRegistered;
use App\Notifications\Admin\CollaboratorWaitingApproval;
use App\Services\AdminService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyAdminsCollaboratorAwaitingApproval
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
    public function handle(NewCollaboratorRegistered $event): void
    {
        app(AdminService::class)
            ->notify(new CollaboratorWaitingApproval($event->user));
    }
}
