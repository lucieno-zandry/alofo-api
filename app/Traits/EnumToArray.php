<?php

namespace App\Traits;

trait EnumToArray
{
    public static function toArray(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}

