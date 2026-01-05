<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum TransactionStatus: string
{
    use EnumToArray;

    case FAILED = 'FAILED';
    case PENDING =  'PENDING';
    case SUCCESS =  'SUCCESS';
}
