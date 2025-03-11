<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\VariantOptionCreateRequest;
use App\Http\Requests\VariantOptionDeleteRequest;
use App\Http\Requests\VariantOptionUpdateRequest;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use DB;
use Illuminate\Http\Request;

class VariantOptionController extends Controller
{
    public function store(VariantOptionCreateRequest $request)
    {
        $data = $request->validated();
        $variant_option = VariantOption::create($data);

        if (isset($data['variant_ids']))
            DB::table('variant_variant_option')
                ->insert(Functions::array_from(
                    [
                        'variant_option_id' => $variant_option->id
                    ],
                    $data['variant_ids'],
                    'variant_id'
                ));

        return [
            'variant_option' => $variant_option
        ];
    }

    public function update(VariantOptionUpdateRequest $request, VariantOption $variant_option)
    {
        $data = $request->validated();

        $variant_option->update($data);

        if ($request->has('variant_ids')) {
            $variant_variant_option = DB::table('variant_variant_option');

            // Delete linked variant and options
            $variant_variant_option
                ->where('variant_option_id', operator: $variant_option->id)
                ->delete();

            // Save new linked variants
            if (!empty($request->variant_ids)) {
                $variant_variant_option
                    ->insert(Functions::array_from(
                        [
                            'variant_option_id' => $variant_option->id
                        ],
                        $request->variant_ids,
                        'variant_id'
                    ));
            }
        }

        return [
            'variant_option' => $variant_option
        ];
    }

    public function destroy(VariantOptionDeleteRequest $request)
    {
        $variant_option_ids = explode(',', $request->variant_option_ids);
        $deleted = VariantOption::whereIn('id', $variant_option_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function index()
    {
        $variant_options = VariantOption::applyFilters()->get();

        return [
            'variant_options' => $variant_options
        ];
    }

    public function show(int $id)
    {
        $variant_option = VariantOption::withRelations()->find($id);

        return [
            'variant_option' => $variant_option
        ];
    }
}
