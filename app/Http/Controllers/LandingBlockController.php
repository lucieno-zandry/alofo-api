<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Models\LandingBlock;
use App\Http\Requests\StoreLandingBlockRequest;
use App\Http\Requests\UpdateLandingBlockRequest;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingBlockController extends Controller
{
    use AuthorizesRequests;

    protected function hydrateLandingAble(LandingBlock $block): void
    {

        $related = $block->landing_able;
        if (!$related) return;

        switch ($block->block_type) {
            case 'hero':
                if ($related instanceof Product) {
                    $related->hydrateVariants();
                }

                break;

            case 'cta_banner':
                if ($related instanceof Product) {
                    $related->hydrateVariants();
                }
                break;

            default:
                break;
        }
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'landing_able_type' => 'string',
            'landing_able_id' => 'numeric'
        ]);

        $query = LandingBlock::with(['landing_able', 'image']);

        if (!empty($filters))
            foreach ($filters as $key => $value) {
                $query->where($key, $value);
            }

        $blocks = $query
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        foreach ($blocks as $block)
            $this->hydrateLandingAble($block);

        return response()->json($blocks);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LandingBlock::class);

        $blocks = LandingBlock::with(['landing_able', 'image'])
            ->orderBy('display_order')
            ->get();

        foreach ($blocks as $block)
            $this->hydrateLandingAble($block);

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
