<?php

namespace App\Listeners;

use App\Events\Payment;
use App\Notifications\PaymentSuccess;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyBuyerTransactionSuccess implements ShouldQueue
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
        $order = $event->order;
        $transaction = $event->transaction;

        $user = $order->user;
        $user->notify(new PaymentSuccess($transaction, $order));
    }
}
