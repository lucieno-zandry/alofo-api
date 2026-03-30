<?php

namespace App\Http\Controllers;

use App\Helpers\CategoryHelpers;
use App\Helpers\Functions;
use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\CategoryDeleteRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use Carbon\Carbon;

use function Symfony\Component\Clock\now;

class CategoryController extends Controller
{
    public function store(CategoryCreateRequest $request)
    {
        $data = $request->validated();
        $category = Category::create($data);

        cache()->forget('categories');

        return [
            'category' => $category
        ];
    }

    public function update(CategoryUpdateRequest $request, Category $category)
    {
        $data = $request->validated();
        $category->update($data);

        cache()->forget('categories');

        return [
            'category' => $category
        ];
    }

    public function destroy(CategoryDeleteRequest $request)
    {
        $category_ids = explode(',', $request->category_ids);
        $deleted = Category::whereIn('id', $category_ids)->delete();

        cache()->forget('categories');

        return [
            'deleted' => $deleted
        ];
    }

    public function index()
    {
        $query = Category::applyFilters();

        $key = 'categories';
        $ttl = Carbon::now()->addDay();

        $categories = cache()->remember($key, $ttl, function () use ($query) {
            return $query->get();
        });

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
