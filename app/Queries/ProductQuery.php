<?php

namespace App\Queries;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductQuery
{
    public static function make(Request $request)
    {
        if ($request->filled('search')) {
            return Product::search($request->search)
                ->query(function ($query) use ($request) {
                    // All Eloquent-specific logic goes here
                    return $query->with($request->relations())
                        ->orderBySafe($request->orderBy(), $request->direction());
                });
        }

        // Normal Eloquent flow
        return Product::query()
            ->with($request->relations())
            ->orderBySafe($request->orderBy(), $request->direction());
    }
}
