<?php

namespace App\Http\Controllers;

use App\Helpers\CategoryHelpers;
use App\Helpers\Functions;
use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\CategoryDeleteRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    public function store(CategoryCreateRequest $request)
    {
        $data = $request->validated();
        $category = Category::create($data);

        return [
            'category' => $category
        ];
    }

    public function update(CategoryUpdateRequest $request, Category $category)
    {
        $data = $request->validated();
        $category->update($data);

        return [
            'category' => $category
        ];
    }

    public function destroy(CategoryDeleteRequest $request)
    {
        $category_ids = explode(',', $request->category_ids);
        $deleted = Category::whereIn('id', $category_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function index()
    {
        $categories = Category::applyFilters()->get();

        return [
            'categories' => $categories
        ];
    }

    public function show(int $id)
    {
        $category = Category::withRelations()->find($id);

        return [
            'category' => $category
        ];
    }

    public function hierarchy()
    {
        $categories = CategoryHelpers::get_hierarchy();

        return [
            'categories' => $categories
        ];
    }
}