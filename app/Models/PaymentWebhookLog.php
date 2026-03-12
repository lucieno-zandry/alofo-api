<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookLog extends Model
{
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_uuid');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_uuid');
    }
}
