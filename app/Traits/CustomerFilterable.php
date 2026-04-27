<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CustomerFilterable
{
    public function scopeCustomerFilterable(Builder $query): Builder
    {
        if (auth('sanctum')->user()->roleIsCustomer()) {
            return $query->where('user_id', auth('sanctum')->id());
        }

        return $query;
    }
}
