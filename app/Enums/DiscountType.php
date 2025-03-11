<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum DiscountType: string
{
    use EnumToArray;
    
    case FIXED_AMOUNT = "FIXED_AMOUNT";
    case PERCENTAGE = "PERCENTAGE";
}
