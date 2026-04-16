<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\VariantCreateRequest;
use App\Http\Requests\VariantDeleteRequest;
use App\Http\Requests\VariantIndexRequest;
use App\Http\Requests\VariantUpdateRequest;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VariantController extends Controller
{
    public function store(VariantCreateRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $image = Functions::store_uploaded_image($request->file('image'), 'products');
            $data['image_id'] = $image->id;
        }

        $variant = Variant::create($data);

        if (isset($data['variant_option_ids']))
            $variant
                ->variant_options()
                ->sync($data['variant_option_ids']);

        if (isset($data['promotion_id']))
            $variant
                ->promotions()
                ->attach($data['promotion_id']);

        return [
            'variant' => $variant
        ];
    }

    public function update(VariantUpdateRequest $request, Variant $variant)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Remove existing image if changed
            if ($variant->image)
                Storage::delete(paths: $variant->image->path);

            $image = Functions::store_uploaded_image($request->file('image'), 'products');
            $data['image_id'] = $image->id;
        }

        if ($request->has('variant_option_ids')) {
            $variant_variant_option = DB::table('variant_variant_option');

            // Delete linked variant and options
            $variant_variant_option
                ->where('variant_id', $variant->id)
                ->delete();

            // Save new linked options
            if (!empty($request->variant_option_ids)) {
                $variant_variant_option
                    ->insert(Functions::array_from(
                        [
                            'variant_id' => $variant->id
                        ],
                        $request->variant_option_ids,
                        'variant_option_id'
                    ));
            }
        }

        $variant->update($data);

        if (isset($data['promotion_id']))
            $variant
                ->promotions()
                ->attach($data['promotion_id']);

        return [
            'variant' => $variant
        ];
    }

    public function destroy(VariantDeleteRequest $request)
    {
        $variant_ids = explode(',', $request->variant_ids);
        $deleted = Variant::whereIn('id', $variant_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    /**
     * List variants with filtering, sorting, and pagination.
     *
     * @param VariantIndexRequest $request
     * @return JsonResponse
     */
    public function index(VariantIndexRequest $request)
    {
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = Variant::withRelations();

        // Apply filters
        $query->filter($request->validated());

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $variants = $query->paginate($perPage);

        // Transform each variant (e.g., currency conversion)
        foreach ($variants as $variant) {
            $variant->convertCurrency(); // assume this method exists
        }

        return response()->json($variants);
    }

    public function show(int $id)
    {
        /** @var \App\Models\Variant | null */
        $variant = Variant::withRelations()->find($id);

        $variant?->convertCurrency();

        return [
            'variant' => $variant
        ];
    }
}
