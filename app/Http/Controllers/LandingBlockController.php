<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Models\LandingBlock;
use App\Http\Requests\StoreLandingBlockRequest;
use App\Http\Requests\UpdateLandingBlockRequest;
use App\Models\Category;
use App\Models\Image;
use App\Models\Variant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingBlockController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get the cheapest variant across all products in a category.
     * Returns null if no products/variants exist.
     */
    private function getCheapestVariantForCategory(Category $category)
    {
        // Subquery to find the cheapest variant price for products in this category
        $cheapestVariant = Variant::whereHas('product', function ($query) use ($category) {
            $query->where('category_id', $category->id);
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

    public function publicIndex(Request $request): JsonResponse
    {
        $blocks = LandingBlock::with(['landing_able', 'image'])
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        // Hydrate additional relations based on block type
        foreach ($blocks as $block) {
            $related = $block->landing_able;
            if (!$related) continue;

            switch ($block->block_type) {
                case 'hero':
                    if ($block->landing_able_type === 'App\\Models\\Product') {
                        // Load product with variants, variant groups, and options
                        $related->load([
                            'variants.variant_options',
                            // 'variants.image',
                            'variant_groups.variant_options',
                            // 'images',
                        ]);
                        $related->convertCurrency();
                    }
                    break;

                case 'collection_grid':
                    if ($block->landing_able_type === 'App\\Models\\Category') {
                        // For each category, attach a "cheapest_variant" attribute
                        // that contains the cheapest product variant in that category
                        $related->cheapest_variant = $this->getCheapestVariantForCategory($related);
                    }
                    break;

                default:
                    break;
                    // Add other block types as needed
            }
        }

        return response()->json($blocks);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LandingBlock::class);

        $blocks = LandingBlock::with(['landing_able', 'image'])
            ->orderBy('display_order')
            ->when($request->boolean('active_only'), fn($q) => $q->where('is_active', true))
            ->get();

        return response()->json($blocks);
    }

    public function store(StoreLandingBlockRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = Functions::store_uploaded_image($request->image, 'landing-blocks');
            $imageId = $image->id;
            $data['image_id'] = $imageId;
        }

        $block = LandingBlock::create($data);
        $block->load(['landing_able', 'image']);

        return response()->json($block, 201);
    }

    public function show(LandingBlock $landingBlock): JsonResponse
    {
        $this->authorize('view', $landingBlock);

        $landingBlock->load(['landing_able', 'image']);

        return response()->json($landingBlock);
    }

    public function update(UpdateLandingBlockRequest $request, LandingBlock $landingBlock): JsonResponse
    {
        $data = $request->validated();

        // Handle image replacement
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($landingBlock->image_id) {
                Image::find($landingBlock->image_id)->delete();
            }
            $image = Functions::store_uploaded_image($request->image, 'landing-blocks');
            $imageId = $image->id;
            $data['image_id'] = $imageId;
        }

        // Handle explicit removal of image
        if ($request->boolean('remove_image')) {
            if ($landingBlock->image_id) {
                Image::find($landingBlock->image_id)->delete();
            }
            $data['image_id'] = null;
        }

        $landingBlock->update($data);
        $landingBlock->load(['landing_able', 'image']);

        return response()->json($landingBlock);
    }

    public function destroy(LandingBlock $landingBlock): JsonResponse
    {
        $this->authorize('delete', $landingBlock);

        if ($landingBlock->image_id) {
            Image::find($landingBlock->image_id)->delete();
        }

        $landingBlock->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', LandingBlock::class);

        $request->validate([
            'blocks' => 'required|array',
            'blocks.*.id' => 'required|exists:landing_blocks,id',
            'blocks.*.display_order' => 'required|integer|min:0',
        ]);

        foreach ($request->blocks as $item) {
            LandingBlock::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }

        return response()->json(['message' => 'Order updated']);
    }
}
