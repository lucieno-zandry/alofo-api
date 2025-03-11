<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\PromotionCreateRequest;
use App\Http\Requests\PromotionDeleteRequest;
use App\Http\Requests\PromotionUpdateRequest;
use App\Models\Promotion;
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
        $promotion_ids = implode(',', $request->promotion_ids);
        $deleted = Promotion::whereIn('id', $promotion_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function index()
    {
        $promotions = Promotion::applyFilters()->active()->get();

        return [
            'promotions' => $promotions
        ];
    }

    public function show(int $promotion_id)
    {
        $promotion = Promotion::withRelations()->find($promotion_id);

        return [
            'promotion' => $promotion
        ];
    }
}
