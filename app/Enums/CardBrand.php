<?php

namespace App\Enums;

enum CardBrand: string
{
    case Visa = 'visa';
    case Mastercard = 'mastercard';
    case Amex = 'amex';
    case Discover = 'discover';
    case Diners = 'diners';
    case Jcb = 'jcb';
    case Unknown = 'unknown';
}
