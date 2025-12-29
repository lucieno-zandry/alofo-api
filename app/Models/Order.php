<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\CustomerFilterable;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use WithOrdering, WithPagination, WithRelationships, CustomerFilterable, DynamicConditionApplicable, ApplyFilters;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'address_snapshot' => 'array',
        'coupon_snapshot'  => 'array',
    ];

    protected $fillable = [
        'address_id',
    ];

    public function has_no_successful_payment()
    {
        $successful_transaction = Transaction::where('order_uuid', $this->uuid)->first();
        return !$successful_transaction;
    }

    public function cart_items()
    {
        return $this->hasMany(CartItem::class, 'order_uuid', 'uuid');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'order_uuid');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }
}
