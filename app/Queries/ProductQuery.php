<?php

namespace App\Queries;

use App\Http\Requests\ProductIndexRequest;
use App\Models\Product;
use App\Services\CurrencyService;
use Laravel\Scout\Builder;

class ProductQuery
{
    public static function make(ProductIndexRequest $request): Builder
    {
        $relevancySorting = $request->orderBy() === 'created_at' && $request->direction() === 'DESC';

        $search = $request->filled('search') ? $request->search : '*';
        $builder = Product::search($search);

        if ($request->filled('category_id')) {
            $builder->where('category_id', (int) $request->category_id);
        }

        if ($request->filled('min_price')) {
            $min_price = app(CurrencyService::class)->invert($request->min_price);
            $builder->where('price_min', '>=' . (float) $min_price);
        }

        if ($request->filled('max_price')) {
            $max_price = app(CurrencyService::class)->invert($request->max_price);
            $builder->where('price_max', '<=' . (float) $max_price);
        }

        if ($request->filled('variant_option_ids')) {
            // Ensure the array contains integers
            $ids = is_array($request->variant_option_ids)
                ? array_map('intval', $request->variant_option_ids)
                : [(int) $request->variant_option_ids];

            $builder->whereIn('variant_option_ids', $ids);
        }

        $builder->options(['infix' => 'always']);

        $builder = $builder->query(function ($query) use ($request) {
            return $query->with($request->relations());
        });

        if (!$relevancySorting) {
            $builder = $builder->orderBy($request->orderBy(), $request->direction());
        }

        return $builder;
    }
}
