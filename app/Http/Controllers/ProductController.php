<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductDeleteRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
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
                $filename = Functions::store_uploaded_file($file, ['folder' => 'products']);
                $image = Image::create(['filename' => $filename]);
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

        if ($request->has('old_images_filenames')) {
            $product
                ->images()
                ->whereIn('filename', $request->old_images_filenames)
                ->delete();

            Storage::delete($request->old_images_filenames);
        }

        if ($request->has('images')) {
            $images = new Collection();

            foreach ($request->images as $file) {
                $filename = Functions::store_uploaded_file($file, ['folder' => 'products']);
                $image = Image::create(['filename' => $filename]);
                $images->add($image);
            }

            $product->images()->sync($images);
        }

        return [
            'product' => $product
        ];
    }

    public function destroy(ProductDeleteRequest $request)
    {
        $product_ids = explode(',', $request->product_ids);
        $deleted = Product::whereIn('id', $product_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function index()
    {
        $products = Product::applyFilters()->get();

        return [
            'products' => $products
        ];
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
