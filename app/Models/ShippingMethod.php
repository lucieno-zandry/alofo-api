<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'carrier',
        'is_active',
        'calculation_type',
        'flat_rate',
        'free_shipping_threshold',
        'rate_per_kg',
        'api_config',
        'min_delivery_days',
        'max_delivery_days',
        'allowed_countries'
    ];

    protected $casts = [
        'allowed_countries' => 'array'
    ];

    public function shipping_rates()
    {
        return $this->hasMany(ShippingRate::class);
    }
}
