<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Helpers\ProductFullUpdateService;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductDeleteRequest;
use App\Http\Requests\ProductFullCreateRequest;
use App\Http\Requests\ProductFullUpdateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Image;
use App\Models\Product;
use App\Queries\ProductQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function store(ProductCreateRequest $request)
    {
        $data = $request->validated();
        $product = Product::create($data);

        if ($request->hasFile('images')) {
            $imageIds = [];

            foreach ($request->file('images') as $file) {
                $image = Functions::store_uploaded_image($file, 'products');
                $imageIds[] = $image->id;
            }

            $product->images()->attach($imageIds);
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
                $image->delete();
            }
        }

        // 2. Add new images
        if ($request->hasFile('images')) {
            $imageIds = [];

            foreach ($request->file('images') as $file) {
                $image = Functions::store_uploaded_image($file, 'products');
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
            ->paginate($request->limit ?? 20);

        return response()->json($products);
    }

    public function show(string $slug)
    {
        $product = Product::with([
            'variant_groups' => fn($query) => $query->with('variant_options'),
            'variants' => fn($query) => $query->with('variant_options'),
            'images'
        ])->where('slug', $slug)->first();

        return [
            'product' => $product
        ];
    }

    public function product_full_create(ProductFullCreateRequest $request)
    {
        $product = DB::transaction(function () use ($request) {

            $product = Product::create([
                'title' => $request->title,
                'slug' => $request->slug,
                'description' => $request->description,
                'category_id' => $request->category_id,
            ]);

            // images
            if ($request->hasFile('images')) {
                $imageIds = [];

                foreach ($request->file('images') as $file) {
                    $image = Functions::store_uploaded_image($file, 'products');

                    if ($image && $image->id) {
                        $imageIds[] = $image->id;
                    }
                }

                $product->images()->sync($imageIds);
            }

            // groups + options
            $optionMap = [];

            foreach ($request->variant_groups ?? [] as $groupData) {

                $group = $product->variant_groups()->create([
                    'name' => $groupData['name']
                ]);

                foreach ($groupData['options'] ?? [] as $opt) {
                    $option = $group->variant_options()->create([
                        'value' => $opt['value']
                    ]);

                    // ⚠️ better key than value alone (explained below)
                    $optionMap[$group->name . ':' . $opt['value']] = $option->id;
                }
            }

            // variants
            foreach ($request->variants ?? [] as $variantData) {

                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'special_price' => $variantData['special_price'] ?? null,
                    'stock' => $variantData['stock'],
                ]);

                if (!empty($variantData['option_refs'])) {
                    $ids = collect($variantData['option_refs'])
                        ->map(fn($ref) => $optionMap[$ref])
                        ->toArray();

                    $variant->variant_options()->sync($ids);
                }
            }

            return $product;
        });

        return response()->json(
            [
                'product' => $product->load([
                    'images',
                    'variant_groups.variant_options',
                    'variants.variant_options',
                ]),
            ],
            201
        );
    }

    public function product_full_update(
        ProductFullUpdateRequest $request,
        Product $product,
        ProductFullUpdateService $service
    ) {
        $updated = $service->update($product, $request);
        return response()->json(['product' => $updated]);
    }
}
