<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum UserRole: string
{
    use EnumToArray;

    case CLIENT = "client";
    case ADMIN = "admin";
    case MANAGER = "manager";
}