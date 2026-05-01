<?php

namespace App\Listeners;

use App\Events\Payment;
use App\Notifications\Admin\TransactionSuccessAdmin;
use App\Services\AdminService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyAdminsTransactionSuccess
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
    public function handle(Payment $event): void
    {
        $admins = app(AdminService::class);
        $admins->notify(new TransactionSuccessAdmin($event->transaction, $event->order));
    }
}
