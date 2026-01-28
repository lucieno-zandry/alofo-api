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

class Variant extends Model
{
    /** @use HasFactory<\Database\Factories\VariantFactory> */
    use HasFactory, WithPagination, WithOrdering, WithRelationships, DynamicConditionApplicable, ApplyFilters;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'special_price',
        'stock',
        'image'
    ];

    public function get_price(): float
    {
        $price = $this->price;

        if (auth()->user()->canUseSpecialPrices()) {
            $price = $this->special_price;
        }

        return $price;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant_options()
    {
        return $this->belongsToMany(VariantOption::class);
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class);
    }

    public function scopeWithRelations(Builder $query)
    {
        if (request()->has('with')) {
            $relations = explode(',', request('with'));

            foreach ($relations as $relation) {
                $query->with([
                    $relation => function ($query) use ($relation) {
                        if ($relation === 'promotions')
                            $query->active();

                        $query->latest('id');
                    }
                ]);
            }
        }

        return $query;
    }
}
