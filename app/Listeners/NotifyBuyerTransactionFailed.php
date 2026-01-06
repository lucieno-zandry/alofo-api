<?php

namespace App\Listeners;

use App\Events\FailedPayment;
use App\Notifications\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyBuyerTransactionFailed implements ShouldQueue
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
    public function handle(FailedPayment $event): void
    {
        $order = $event->order;
        $transaction = $event->transaction;

        $user = $order->user;
        $user->notify(new PaymentFailed($transaction, $order));
    }
}