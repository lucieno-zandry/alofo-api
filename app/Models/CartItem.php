<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory, WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters;

    protected $fillable = [
        'count',
        'variant_id',
        'promotion_id',
        'user_id'
    ];

    public function is_not_ordered()
    {
        return !$this->order || $this->order->has_no_successful_payment();
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function scopeNotOrdered(Builder $builder)
    {
        $builder->where('order_uuid', null);
        return $builder;
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_uuid', 'uuid');
    }
}
