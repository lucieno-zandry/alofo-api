<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\VariantCreateRequest;
use App\Http\Requests\VariantDeleteRequest;
use App\Http\Requests\VariantUpdateRequest;
use App\Models\Variant;
use DB;
use Illuminate\Validation\ValidationException;
use Storage;

class VariantController extends Controller
{
    public function store(VariantCreateRequest $request)
    {
        $data = $request->validated();

        if (!empty($data['image'])) {
            $path = Functions::store_uploaded_file($data['image'], ['folder' => 'products']);

            if (!$path)
                throw ValidationException::withMessages(['image' => 'Failed to store image']);

            $data['image'] = $path;
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

        // Remove existing image if changed
        if (key_exists("image", $data) && $variant->image)
            Storage::delete(paths: $variant->image);

        // Store the uploaded image
        if (!empty($data['image'])) {
            $path = Functions::store_uploaded_file($data['image']);

            if (!$path)
                throw ValidationException::withMessages(['image' => 'Failed to store image']);

            $data['image'] = $path;
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

    public function index()
    {
        $variants = Variant::applyFilters()->get();

        return [
            'variants' => $variants
        ];
    }

    public function show(int $id)
    {
        $variant = Variant::withRelations()->find($id);

        return [
            'variant' => $variant
        ];
    }
}