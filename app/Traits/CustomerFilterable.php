<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CustomerFilterable
{
    public function scopeCustomerFilterable(Builder $query): Builder
    {
        if (auth()->user()->roleIsCustomer()) {
            return $query->where('user_id', auth()->id());
        }

        return $query;
    }
}
