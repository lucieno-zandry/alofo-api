<?php

// app/Observers/OrderObserver.php
namespace App\Observers;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;

class OrderObserver
{
    public function created(Order $order): void
    {
        Shipment::create([
            'status' => ShipmentStatus::PENDING->value,
            'order_uuid' => $order->uuid,
            'is_active' => true,
        ]);
    }
}
