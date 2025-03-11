<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\VariantGroupCreateRequest;
use App\Http\Requests\VariantGroupDeleteRequest;
use App\Http\Requests\VariantGroupUpdateRequest;
use App\Models\VariantGroup;
use Illuminate\Http\Request;

class VariantGroupController extends Controller
{
    public function store(VariantGroupCreateRequest $request)
    {
        $data = $request->validated();
        $variant_group = VariantGroup::create($data);

        return [
            'variant_group' => $variant_group
        ];
    }

    public function update(VariantGroupUpdateRequest $request, VariantGroup $variant_group)
    {
        $data = $request->validated();
        $variant_group->update($data);

        return [
            'variant_group' => $variant_group
        ];
    }

    public function destroy(VariantGroupDeleteRequest $request)
    {
        $variant_group_ids = explode(',', $request->variant_group_ids);

        $deleted = VariantGroup::whereIn('id', $variant_group_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function index()
    {
        $variant_groups = VariantGroup::applyFilters()->get();

        return [
            'variant_groups' => $variant_groups
        ];
    }

    public function show(int $variant_group_id)
    {
        $variant_group = VariantGroup::withRelations()->find($variant_group_id);

        return [
            'variant_group' => $variant_group
        ];
    }
}
