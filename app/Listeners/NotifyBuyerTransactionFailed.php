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
        $order_detail_url = Functions::get_frontend_url("CUSTOMER_ORDER_DETAILS_PATHNAME");

        /** @var \App\Models\User */
        $user = $order->user;
        
        if ($user->canUseNotifications())
            $user->notify(new PaymentFailed($transaction, $order, $order_detail_url));
    }
}
