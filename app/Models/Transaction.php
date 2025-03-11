<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\CustomerFilterable;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use WithRelationships, WithPagination, WithOrdering, ApplyFilters, DynamicConditionApplicable, CustomerFilterable;

    public static $STATUS_SUCCESS = 'SUCCESS';
    public static $STATUS_FAILED = 'FAILED';
    public static $STATUS_PENDING = 'PENDING';

    protected $fillable = [
        'status',
        'informations',
        'user_id',
        'order_uuid'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_uuid');
    }
}
