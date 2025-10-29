<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // --- Create some categories ---
        $categories = Category::factory(5)->create();

        $categories->each(function ($category) {
            // Each category gets between 2–4 products
            $products = Product::factory(rand(2, 4))->create([
                'category_id' => $category->id,
            ]);

            $products->each(function ($product) {
                // Create between 1 and 3 variant groups (e.g., Color, Size)
                $variantGroups = collect(['Color', 'Size', 'Material'])
                    ->random(rand(1, 3))
                    ->map(function ($groupName) use ($product) {
                        return VariantGroup::factory()->create([
                            'product_id' => $product->id,
                            'name' => $groupName,
                        ]);
                    });

                // For each variant group, create 2–4 options
                $optionsByGroup = [];
                foreach ($variantGroups as $group) {
                    $options = VariantOption::factory(rand(2, 4))->create([
                        'variant_group_id' => $group->id,
                    ]);
                    $optionsByGroup[$group->name] = $options;
                }

                // Generate combinations of all variant options (Cartesian product)
                $optionSets = $this->generateOptionCombinations($optionsByGroup);

                // Create a variant for each combination
                foreach ($optionSets as $set) {
                    $variant = Variant::factory()->create([
                        'product_id' => $product->id,
                        'sku' => strtoupper(Str::random(10)),
                    ]);

                    // Attach the variant options to this variant
                    $variant->variant_options()->sync($set);
                }
            });
        });
    }

    /**
     * Generate Cartesian combinations of variant options.
     * Example:
     *  [
     *    'Color' => [1, 2],
     *    'Size' => [3, 4]
     *  ]
     * → returns [[1,3], [1,4], [2,3], [2,4]]
     */
    private function generateOptionCombinations(array $optionsByGroup): array
    {
        $groups = array_values($optionsByGroup);
        if (empty($groups)) {
            return [];
        }

        $combinations = [[]];
        foreach ($groups as $options) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($options as $option) {
                    $newCombinations[] = array_merge($combination, [$option->id]);
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }
}
