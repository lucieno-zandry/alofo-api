<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\PromotionCreateRequest;
use App\Http\Requests\PromotionDeleteRequest;
use App\Http\Requests\PromotionUpdateRequest;
use App\Http\Requests\PromotionVariantAttachRequest;
use App\Http\Requests\PromotionVariantDetachRequest;
use App\Models\Promotion;
use Illuminate\Http\Request;
use App\Http\Requests\PromotionBulkAttachVariantsRequest;
use Illuminate\Support\Facades\DB;


class PromotionController extends Controller
{
    public function store(PromotionCreateRequest $request)
    {
        $data = $request->validated();
        $promotion = Promotion::create($data);

        if (isset($data['variant_ids'])) {
            $promotion->variants()->sync($data['variant_ids']);
        }

        return [
            'promotion' => $promotion
        ];
    }

    public function update(PromotionUpdateRequest $request, Promotion $promotion)
    {
        $data = $request->validated();

        if (isset($data['variant_ids'])) {
            $promotion->variants()->sync($data['variant_ids']);
        }

        $promotion->update($data);

        return [
            'promotion' => $promotion
        ];
    }

    public function destroy(PromotionDeleteRequest $request)
    {
        $promotion_ids = explode(',', $request->promotion_ids);
        $deleted = Promotion::whereIn('id', $promotion_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function index(Request $request)
    {
        // Allowed sort columns to prevent SQL injection
        $allowedSortColumns = ['id', 'name', 'type', 'is_active', 'created_at', 'updated_at', 'expires_at'];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort column
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

        // Prepare eager loads
        $withParam = $request->get('with');
        $relations = $withParam ? explode(',', $withParam) : [];

        // Build query
        $query = Promotion::query();

        // Apply search filter (search on name and description)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply active status filter (if provided and not 'all')
        if ($request->has('is_active') && $request->get('is_active') !== 'all') {
            $isActive = filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Apply type filter (if provided and not 'all')
        if ($request->has('type') && $request->get('type') !== 'all') {
            $type = $request->get('type');
            $query->where('type', $type);
        }

        // Apply applies_to filter (if provided and not 'all')
        if ($request->has('applies_to') && $request->get('applies_to') !== 'all') {
            $appliesTo = $request->get('applies_to');
            $query->where('applies_to', $appliesTo);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Apply eager loading
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Paginate
        $perPage = $request->get('per_page', 15);
        $promotions = $query->paginate($perPage);

        return response()->json($promotions);
    }

    public function show(int $promotion_id)
    {
        $promotion = Promotion::withRelations()->find($promotion_id);

        return [
            'promotion' => $promotion
        ];
    }

    public function attachVariant(PromotionVariantAttachRequest $request, Promotion $promotion)
    {
        $promotion->variants()->attach($request->variant_id);

        return [
            'message' => 'Variant attached successfully'
        ];
    }

    public function detachVariant(PromotionVariantDetachRequest $request, Promotion $promotion)
    {
        $promotion->variants()->detach($request->variant_id);

        return [
            'message' => 'Variant detached successfully'
        ];
    }

    /**
     * Attach multiple variants to a promotion in bulk.
     *
     * @param PromotionBulkAttachVariantsRequest $request
     * @param Promotion $promotion
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAttachVariants(PromotionBulkAttachVariantsRequest $request, Promotion $promotion)
    {
        $variantIds = $request->input('variant_ids');

        // Get currently attached variant IDs
        $currentVariantIds = $promotion->variants()->pluck('variants.id')->toArray();

        // Separate already attached IDs
        $alreadyAttached = array_intersect($variantIds, $currentVariantIds);
        $newIds = array_diff($variantIds, $alreadyAttached);

        $attached = [];
        $failed = [];

        if (!empty($newIds)) {
            try {
                DB::beginTransaction();

                // Use syncWithoutDetaching to attach only new ones
                $result = $promotion->variants()->syncWithoutDetaching($newIds);

                // $result['attached'] contains IDs that were newly attached
                $attached = $result['attached'];

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                // In case of error, mark all new IDs as failed
                $failed = $newIds;
                $attached = [];
            }
        }

        // Build response
        $attachedCount = count($attached);
        $skippedCount = count($alreadyAttached);
        $failedCount = count($failed);

        $message = sprintf(
            '%d variant%s attached, %d already attached, %d failed.',
            $attachedCount,
            $attachedCount === 1 ? '' : 's',
            $skippedCount,
            $failedCount
        );

        return response()->json([
            'message' => $message,
            'attached' => $attached,
            'skipped' => $alreadyAttached,
            'failed' => $failed,
        ]);
    }
}
