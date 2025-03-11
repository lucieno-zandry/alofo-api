<?php

namespace App\Listeners;

use App\Events\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UseCoupon implements ShouldQueue
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
        $coupon = $event->coupon;

        if (!$coupon)
            return;

        $coupon->uses_count++;
        $coupon->save();
    }
}
