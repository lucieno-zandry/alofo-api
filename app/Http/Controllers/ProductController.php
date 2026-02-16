<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductDeleteRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Image;
use App\Models\Product;
use App\Queries\ProductQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function store(ProductCreateRequest $request)
    {
        $data = $request->validated();
        $product = Product::create($data);

        if ($request->has('images')) {
            $images = new Collection();

            foreach ($request->images as $file) {
                $image = Functions::store_uploaded_file($file, 'products');
                $images->add($image);
            }

            $product->images()->sync($images);
        }

        return [
            'product' => $product
        ];
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();
        $product->update($data);

        // 1. Remove old images
        if ($request->filled('old_images_ids')) {
            $images = Image::whereIn('id', $request->old_images_ids)->get();

            foreach ($images as $image) {
                $product->images()->detach($image->id);
                $image->deleteIfUnused();
            }
        }

        // 2. Add new images
        if ($request->hasFile('images')) {
            $imageIds = [];

            foreach ($request->file('images') as $file) {
                $image = Functions::store_uploaded_file($file, 'products');
                $imageIds[] = $image->id;
            }

            $product->images()->attach($imageIds);
        }

        return [
            'product' => $product->load('images'),
        ];
    }

    public function destroy(ProductDeleteRequest $request)
    {
        $product_ids = explode(',', $request->product_ids);
        $user = Auth::user();

        $products = Product::whereIn('id', $product_ids)->get();
        $deleted = 0;

        $products->each(function (Product $product) use ($user, &$deleted) {
            if ($user->can('delete', $product)) {
                $product->delete(); // triggers model events
                $deleted++;
            }
        });

        return [
            'deleted' => $deleted
        ];
    }

    public function index(ProductIndexRequest $request)
    {
        $products = ProductQuery::make($request)
            ->with($request->relations())
            ->orderBySafe($request->orderBy(), $request->direction())
            ->limit($request->limit)
            ->offset($request->offset)
            ->get();

        return response()->json([
            'products' => $products
        ]);
    }


    public function show(string $slug)
    {
        $product = Product::with([
            'variant_groups' => fn($query) => $query->with('variant_options'),
            'variants' => fn($query) => $query->with('variant_options'),
        ])->where('slug', $slug)->first();

        return [
            'product' => $product
        ];
    }

    public function search(string $keywords)
    {
        $to_search = Functions::sanitize_search_query($keywords);

        $products = Product::applyFilters()
            ->select('products.*')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('variant_groups', 'variant_groups.product_id', '=', 'products.id')
            ->leftJoin('variant_variant_option', 'variant_variant_option.variant_id', '=', 'variant_groups.id')
            ->leftJoin('variant_options', 'variant_options.id', '=', 'variant_variant_option.variant_option_id')
            ->whereRaw("MATCH(products.title, products.description) AGAINST (? IN BOOLEAN MODE)", [$to_search])
            ->orWhereRaw("MATCH(variant_options.value) AGAINST (? IN BOOLEAN MODE)", [$to_search])
            ->orWhereRaw("MATCH(categories.title) AGAINST (? IN BOOLEAN MODE)", [$to_search])
            ->distinct()
            ->get();

        return [
            'products' => $products
        ];
    }
}
