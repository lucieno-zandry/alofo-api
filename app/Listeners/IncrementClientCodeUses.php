<?php

namespace App\Listeners;

use App\Events\ClientCodeUsed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class IncrementClientCodeUses implements ShouldQueue
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
        $event->client_code->increment('uses');
    }
}
