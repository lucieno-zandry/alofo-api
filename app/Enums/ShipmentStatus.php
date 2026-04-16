<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum ShipmentStatus: string
{
    use EnumToArray;

    case PENDING = "PENDING";
    case PROCESSING = "PROCESSING";
    case SHIPPED = "SHIPPED";
    case DELIVERED = "DELIVERED";
}
