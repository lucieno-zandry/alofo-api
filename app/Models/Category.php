<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters;

    protected $fillable = [
        'title',
        'parent_id'
    ];

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the cheapest variant across all products in a category.
     * Returns null if no products/variants exist.
     */
    public function getCheapestVariantAttribute(): ?Variant
    {
        // Subquery to find the cheapest variant price for products in this category
        $cheapestVariant = Variant::whereHas('product', function ($query) {
            $query->where('category_id', $this->id);
        })
            ->orderBy('price', 'asc')
            ->with(['product', 'image', 'variant_options.variant_group'])
            ->first();

        if (!$cheapestVariant) {
            return null;
        }

        $cheapestVariant->convertCurrency();

        return $cheapestVariant;
    }
}
