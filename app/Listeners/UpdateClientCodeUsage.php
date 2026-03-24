<?php

namespace App\Listeners;

use App\Events\ClientCodeUsed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateClientCodeUsage
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
    public function handle(ClientCodeUsed $event): void
    {
        if ($event->action === 'attach') {
            $event->client_code->incrementUses();
        }
    }
}
