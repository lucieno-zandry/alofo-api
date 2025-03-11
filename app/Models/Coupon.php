<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use WithOrdering, WithPagination, WithRelationships, DynamicConditionApplicable, ApplyFilters;
    protected $fillable = [
        'code',
        'type',
        'discount',
        'min_order_value',
        'max_uses',
        'uses_count',
        'start_date',
        'end_date',
        'is_active'
    ];

    public function is_active()
    {
        $now = now();
        return $this->start_date <= $now && $this->end_date > $now && $this->is_active;
    }

    public function is_usable()
    {
        return $this->is_active() && $this->max_uses > $this->uses_count;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
