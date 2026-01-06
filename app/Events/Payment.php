<?php

namespace App\Events;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Payment
{
    use Dispatchable, SerializesModels;

    public ?Coupon $coupon;

    /**
     * Create a new event instance.
     */
    public function __construct(public Order $order, public Transaction $transaction)
    {
        $this->coupon = $order->coupon;
    }
}
