<?php

namespace App\Helpers;

use App\Models\Category;

class CategoryHelpers
{
    public static function get_hierarchy(?int $id = null): array
    {
        $categories = Category::where('parent_id', $id ?? null)->get();

        $hierarchy = $categories->map(function (Category $category) {
            return [
                'category' => $category,
                'children' => self::get_hierarchy($category->id),
            ];
        })->all();

        return $hierarchy;
    }
}