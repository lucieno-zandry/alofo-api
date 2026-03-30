<?php

namespace App\Models;

use App\Services\CurrencyService;
use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\HasEffectivePrice;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory, WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters, HasEffectivePrice;

    protected $fillable = [
        'count',
        'variant_id',
        'user_id',
        'product_id',
        'unit_price',
        'variant_options_snapshot',
        'variant_snapshot',
        'product_snapshot'
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'variant_snapshot' => 'array',
        'variant_options_snapshot' => 'array',
        'applied_promotions_snapshot' => 'array'
    ];

    public function is_not_ordered()
    {
        return !$this->order || $this->order->has_no_successful_payment();
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
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

    public function convertCurrency(): static
    {
        $this->setValuesToConvertedCurrency([
            'promotion_discount_applied' => $this->promotion_discount_applied,
            'total' => $this->total,
            'unit_price' => $this->unit_price,
        ]);

        $this->variant_snapshot = (new Variant)->convertSnapshotCurrency($this->variant_snapshot);
        $this->applied_promotions_snapshot = (new Promotion)->convertSnapshotsCurrency($this->applied_promotions_snapshot);

        if ($this->relationLoaded('variant'))
            $this->variant->setValuesToConvertedCurrency([
                'price' => $this->variant->price,
                'effective_price' => $this->variant->effective_price,
            ]);


        if ($this->relationLoaded('order'))
            $this->order?->convertCurrency();

        if ($this->relationLoaded('product'))
            $this->product?->convertCurrency();

        return $this;
    }
}
