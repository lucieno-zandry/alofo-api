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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Illuminate\Log\log;

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
        $products = ProductQuery::make($request)->paginate($request->limit ?? 20);

        $user = auth('sanctum')->user();

        foreach ($products as $product) {
            if ($product->relationLoaded('variants')) {
                foreach ($product->variants as $variant) {
                    $variant->setEffectivePriceForUser($user);
                }
            }
        }

        return response()->json($products);
    }

    public function show(string $slug): array
    {
        $product = Product::with([
            'variant_groups.variant_options',
            'variants' => fn($q) => $q->with(['variant_options', 'image', 'promotions' => fn($q) => $q->active()]),
            'images'
        ])->where('slug', $slug)->firstOrFail();

        $user = auth('sanctum')->user();

        foreach ($product->variants as $variant) {
            $variant->setEffectivePriceForUser($user);
        }

        return ['product' => $product];
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
                if (!empty($variantData['image'])) {
                    $image = Functions::store_uploaded_image($variantData['image'], 'products');
                    $variantData['image_id'] = $image->id;
                }

                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'image_id' => $variantData['image_id'] ?? null,
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

    public function price_range(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $cacheKey = 'price_range_' . ($request->category_id ?? 'all');
        $ttl = now()->addMinutes(10);

        $range = cache()->remember($cacheKey, $ttl, function () use ($request) {
            $config = config('scout.typesense');
            $clientConfig = $config['client-settings'];
            $client = new \Typesense\Client($clientConfig);

            $searchParams = [
                'q'                => '*',
                'query_by'         => 'title',
                'facet_by'         => 'price_min,price_max',
                'facet_stats'      => true,
                'max_facet_values' => 1,
            ];

            if ($request->filled('category_id')) {
                $searchParams['filter_by'] = 'category_id:=' . (int) $request->category_id;
            }

            $results = $client->collections['products']->documents->search($searchParams);

            $minPrice = 0;
            $maxPrice = 1000;

            foreach ($results['facet_counts'] as $facet) {
                if ($facet['field_name'] === 'price_min' && isset($facet['stats'])) {
                    $minPrice = $facet['stats']['min'];
                }
                if ($facet['field_name'] === 'price_max' && isset($facet['stats'])) {
                    $maxPrice = $facet['stats']['max'];
                }
            }

            // Round min down, max up
            $min = floor($minPrice);
            $max = ceil($maxPrice);

            // Compute dynamic step based on range width
            $rangeWidth = $max - $min;
            $step = max(1, (int) round($rangeWidth / 50)); // At least 1, roughly 2% of range

            return [
                'min'  => (float) $min,
                'max'  => (float) $max,
                'step' => $step,
            ];
        });

        return response()->json($range);
    }
}
