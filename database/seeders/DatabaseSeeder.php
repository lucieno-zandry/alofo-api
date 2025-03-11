<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Variant;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'role' => 'admin',
            'approved_at' => now()
        ]);

        // categories
        $parents = [
            ['title' => 'Electronics'],
            ['title' => 'Clothing'],
        ];

        foreach ($parents as $parentData) {
            $parent = Category::factory()->create($parentData);

            // Define child categories
            $children = [
                ['title' => $parent->title . ' - Phones'],
                ['title' => $parent->title . ' - Laptops'],
                ['title' => $parent->title . ' - Accessories'],
                ['title' => $parent->title . ' - Wearables'],
            ];

            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                Category::create($childData);
            }
        }

        for ($i = 1; $i < 17; $i++) {
            Product::factory(10)->create(['category_id' => $i ]);
            VariantGroup::factory(2)->create(['product_id' => $i]);
            VariantOption::factory(2)->create(['variant_group_id' => $i]);
            Variant::factory(5)->create(['product_id' => $i]);
        }
    }
}
