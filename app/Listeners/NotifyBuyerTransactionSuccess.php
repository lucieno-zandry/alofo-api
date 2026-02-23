<?php

namespace App\Listeners;

use App\Events\Payment;
use App\Helpers\Functions;
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
        $order_detail_url = Functions::get_frontend_url("ORDER_DETAILS_PATHNAME");

        $user = $order->user;
        $user->notify(new PaymentSuccess($transaction, $order, $order_detail_url));
    }
}
