<?php

namespace App\Traits;

trait WithOrdering
{
    public function scopeWithOrdering($query)
    {
        if (request()->has('order_by')) {
            $query->orderBy(request('order_by'), request('direction') ?: 'ASC');
        }

        return $query;
    }
}
