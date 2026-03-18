<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\CustomerFilterable;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use WithOrdering, WithPagination, WithRelationships, DynamicConditionApplicable, ApplyFilters, CustomerFilterable;

    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'status',
        'data',
        'order_uuid',
        'is_active',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
