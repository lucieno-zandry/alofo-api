<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use WithOrdering, WithPagination, WithRelationships, DynamicConditionApplicable, ApplyFilters;
    
    protected $fillable = [
        'status',
        'description',
        'order_uuid'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
