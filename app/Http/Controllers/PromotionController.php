<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\PromotionCreateRequest;
use App\Http\Requests\PromotionDeleteRequest;
use App\Http\Requests\PromotionUpdateRequest;
use App\Http\Requests\PromotionVariantAttachRequest;
use App\Http\Requests\PromotionVariantDetachRequest;
use App\Models\Promotion;
use App\Models\Variant;
use Illuminate\Http\Request;

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
        $per_page = $request->get('per_page', 15);
        $promotions = Promotion::paginate($per_page);

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
}
