<?php

namespace App\Listeners;

use App\Events\FailedPayment;
use App\Helpers\Functions;
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
        $order_detail_url = Functions::get_frontend_url("ORDER_DETAILS_PATHNAME");

        $user = $order->user;
        $user->notify(new PaymentFailed($transaction, $order, $order_detail_url));
    }
}
