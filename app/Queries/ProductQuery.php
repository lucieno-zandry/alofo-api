<?php

namespace App\Queries;

use App\Models\Product;
use Illuminate\Http\Request;
use Laravel\Scout\Builder;

use function Illuminate\Log\log;

class ProductQuery
{
    public static function make(Request $request): Builder
    {
        $search = $request->filled('search') ? $request->search : '*';
        $builder = Product::search($search);

        if ($request->filled('category_id')) {
            $builder->where('category_id', (int) $request->category_id);
        }

        if ($request->filled('min_price')) {
            $builder->where('price_min', '>=' . (float) $request->min_price);
        }
        
        if ($request->filled('max_price')) {
            $builder->where('price_max', '<=' . (float) $request->max_price);
        }

        if ($request->filled('variant_option_ids')) {
            // Ensure the array contains integers
            $ids = is_array($request->variant_option_ids)
                ? array_map('intval', $request->variant_option_ids)
                : [(int) $request->variant_option_ids];

            $builder->whereIn('variant_option_ids', $ids);
        }

        $builder->options(['infix' => 'always']);

        return $builder->query(function ($query) use ($request) {
            return $query->with($request->relations())
                ->orderBySafe($request->orderBy(), $request->direction());
        });
    }
}
