<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $fillable = [
        'country_code',
        'city_pattern',
        'min_weight_kg',
        'max_weight_kg',
        'rate',
        'rate_per_kg',
        'shipping_method_id'
    ];

    public function shipping_method()
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}
