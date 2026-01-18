<?php

namespace App\Queries;

use App\Filters\ProductFilter;
use App\Helpers\Functions;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductQuery
{
    public static function make(Request $request): Builder
    {
        $query = Product::query()
            ->select('products.*')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('variants', 'variants.product_id', '=', 'products.id')
            ->leftJoin('variant_variant_option', 'variant_variant_option.variant_id', '=', 'variants.id')
            ->leftJoin('variant_options', 'variant_options.id', '=', 'variant_variant_option.variant_option_id')
            ->distinct();

        // 🔍 Search
        if ($request->filled('search')) {
            $to_search = Functions::sanitize_search_query($request->search);

            $query->where(function ($q) use ($to_search) {
                $q->whereRaw("MATCH(products.title, products.description) AGAINST (? IN BOOLEAN MODE)", [$to_search])
                    ->orWhereRaw("MATCH(variant_options.value) AGAINST (? IN BOOLEAN MODE)", [$to_search])
                    ->orWhereRaw("MATCH(categories.title) AGAINST (? IN BOOLEAN MODE)", [$to_search]);
            });
        }

        // 🎛 Filters
        $query = (new ProductFilter)->apply($query, $request->all());

        return $query;
    }
}
