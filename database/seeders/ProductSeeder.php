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
        // -----------------------------
        // Categories
        // -----------------------------
        $categories = [
            'Smartphones',
            'Tablets',
            'Laptops',
            'Smartwatches',
            'Accessories'
        ];

        $categoryModels = [];

        foreach ($categories as $title) {
            $categoryModels[$title] = Category::create([
                'title' => $title,
            ]);
        }

        // -----------------------------
        // Samsung Product Catalog (30)
        // -----------------------------
        $products = [
            // Smartphones
            ['Galaxy S24', 'Smartphones'],
            ['Galaxy S24+', 'Smartphones'],
            ['Galaxy S24 Ultra', 'Smartphones'],
            ['Galaxy S23 FE', 'Smartphones'],
            ['Galaxy Z Fold 6', 'Smartphones'],
            ['Galaxy Z Flip 6', 'Smartphones'],
            ['Galaxy A55 5G', 'Smartphones'],
            ['Galaxy A35 5G', 'Smartphones'],
            ['Galaxy A15', 'Smartphones'],
            ['Galaxy XCover 7', 'Smartphones'],

            // Tablets
            ['Galaxy Tab S9', 'Tablets'],
            ['Galaxy Tab S9+', 'Tablets'],
            ['Galaxy Tab S9 Ultra', 'Tablets'],
            ['Galaxy Tab A9', 'Tablets'],
            ['Galaxy Tab A9+', 'Tablets'],

            // Laptops
            ['Galaxy Book4', 'Laptops'],
            ['Galaxy Book4 Pro', 'Laptops'],
            ['Galaxy Book4 Ultra', 'Laptops'],
            ['Galaxy Book4 360', 'Laptops'],
            ['Galaxy Chromebook Go', 'Laptops'],

            // Smartwatches
            ['Galaxy Watch6', 'Smartwatches'],
            ['Galaxy Watch6 Classic', 'Smartwatches'],
            ['Galaxy Watch5 Pro', 'Smartwatches'],
            ['Galaxy Fit3', 'Smartwatches'],
            ['Galaxy Watch FE', 'Smartwatches'],

            // Accessories
            ['Galaxy Buds2 Pro', 'Accessories'],
            ['Galaxy Buds FE', 'Accessories'],
            ['Galaxy SmartTag2', 'Accessories'],
            ['45W Super Fast Charger', 'Accessories'],
            ['Galaxy S Pen Pro', 'Accessories'],
        ];

        foreach ($products as [$title, $categoryName]) {

            $product = Product::create([
                'title' => $title,
                'slug' => Str::slug($title) . "-" . uuid_create(),
                'description' => "The {$title} delivers premium Samsung performance with cutting-edge technology, powerful hardware, and sleek design.",
                'category_id' => $categoryModels[$categoryName]->id,
            ]);

            // -----------------------------
            // Variant Logic (Realistic)
            // -----------------------------

            if ($categoryName === 'Smartphones' || $categoryName === 'Tablets') {
                $this->createDeviceVariants($product);
            }

            if ($categoryName === 'Laptops') {
                $this->createLaptopVariants($product);
            }

            if ($categoryName === 'Smartwatches') {
                $this->createWatchVariants($product);
            }

            if ($categoryName === 'Accessories') {
                $this->createAccessoryVariant($product);
            }
        }
    }

    private function createDeviceVariants(Product $product)
    {
        $storageGroup = VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'Storage'
        ]);

        $colorGroup = VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'Color'
        ]);

        $storages = ['128GB', '256GB', '512GB'];
        $colors = ['Black', 'Silver', 'Blue'];

        $storageOptions = collect($storages)->map(fn($s) =>
            VariantOption::create([
                'variant_group_id' => $storageGroup->id,
                'value' => $s
            ])
        );

        $colorOptions = collect($colors)->map(fn($c) =>
            VariantOption::create([
                'variant_group_id' => $colorGroup->id,
                'value' => $c
            ])
        );

        foreach ($storageOptions as $storage) {
            foreach ($colorOptions as $color) {

                $variant = Variant::create([
                    'product_id' => $product->id,
                    'sku' => strtoupper(Str::slug($product->title . '-' . $storage->value . '-' . $color->value)),
                    'price' => rand(500, 1500),
                    'stock' => rand(0, 50),
                ]);

                $variant->variant_options()->sync([$storage->id, $color->id]);
            }
        }
    }

    private function createLaptopVariants(Product $product)
    {
        $ramGroup = VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'RAM'
        ]);

        $ramOptions = collect(['8GB', '16GB', '32GB'])->map(fn($ram) =>
            VariantOption::create([
                'variant_group_id' => $ramGroup->id,
                'value' => $ram
            ])
        );

        foreach ($ramOptions as $ram) {
            $variant = Variant::create([
                'product_id' => $product->id,
                'sku' => strtoupper(Str::slug($product->title . '-' . $ram->value)),
                'price' => rand(900, 2500),
                'stock' => rand(0, 20),
            ]);

            $variant->variant_options()->sync([$ram->id]);
        }
    }

    private function createWatchVariants(Product $product)
    {
        $sizeGroup = VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'Size'
        ]);

        $sizes = collect(['40mm', '44mm'])->map(fn($size) =>
            VariantOption::create([
                'variant_group_id' => $sizeGroup->id,
                'value' => $size
            ])
        );

        foreach ($sizes as $size) {
            $variant = Variant::create([
                'product_id' => $product->id,
                'sku' => strtoupper(Str::slug($product->title . '-' . $size->value)),
                'price' => rand(250, 500),
                'stock' => rand(0, 40),
            ]);

            $variant->variant_options()->sync([$size->id]);
        }
    }

    private function createAccessoryVariant(Product $product)
    {
        Variant::create([
            'product_id' => $product->id,
            'sku' => strtoupper(Str::slug($product->title)),
            'price' => rand(30, 200),
            'stock' => rand(0, 100),
        ]);
    }
}