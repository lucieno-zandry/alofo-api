<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum TransactionMethod: string
{
    use EnumToArray;

    case VISA = 'VISA';
    case MASTERCARD = 'MASTERCARD';
    case ORANGEMONEY = 'ORANGEMONEY';
    case AIRTELMONEY = 'AIRTELMONEY';
    case MVOLA = 'MVOLA';
    case PAYPAL = 'PAYPAL';
}
