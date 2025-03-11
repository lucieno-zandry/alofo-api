<?php

namespace App\Events;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Payment
{
    use Dispatchable, SerializesModels;

    public ?Coupon $coupon;

    /**
     * Create a new event instance.
     */
    public function __construct(public Order $order)
    {
        $this->coupon = $order->coupon;
    }
}
