<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ProductFilter
{
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                $filters['category_id'] ?? null,
                fn($q, $id) =>
                $q->where('category_id', $id)
            )
            ->when(
                $filters['min_price'] ?? null,
                fn($q, $price) =>
                $q->whereHas(
                    'variants',
                    fn($v) =>
                    $v->where('price', '>=', $price)
                )
            )
            ->when(
                $filters['max_price'] ?? null,
                fn($q, $price) =>
                $q->whereHas(
                    'variants',
                    fn($v) =>
                    $v->where('price', '<=', $price)
                )
            )
            ->when(
                $filters['variant_option_ids'] ?? null,
                fn($q, $ids) =>
                $q->whereHas(
                    'variants.variant_options',
                    fn($vo) =>
                    $vo->whereIn('variant_options.id', $ids)
                )
            );
    }
}
