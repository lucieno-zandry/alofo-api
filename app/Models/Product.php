<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, WithRelationships, WithPagination, DynamicConditionApplicable, ApplyFilters;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'slug'
    ];

    protected static function booted()
    {
        static::deleting(function (Product $product) {
            foreach ($product->images as $image) {
                $product->images()->detach($image->id);
                $image->delete();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable');
    }

    public function variant_groups()
    {
        return $this->hasMany(VariantGroup::class);
    }

    public function cart_items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeOrderBySafe(Builder $query, string $column, string $direction = 'ASC')
    {
        $allowed = ['created_at', 'title'];

        if (in_array($column, $allowed)) {
            $query->orderBy($column, $direction === 'DESC' ? 'DESC' : 'ASC');
        }

        return $query;
    }
}
