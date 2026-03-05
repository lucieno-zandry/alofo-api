<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\Variant;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        // Create a few specific promotions
        $promotions = [
            [
                'name'        => 'Early Bird Special',
                'badge'       => 'EARLY',
                'discount'    => 15,
                'type'        => 'PERCENTAGE',
                'applies_to'  => 'all',
                'stackable'   => true,
                'priority'    => 10,
                'apply_order' => 'percentage_first',
                'max_discount' => null,
            ],
            [
                'name'        => 'Student Discount',
                'badge'       => 'STUDENT',
                'discount'    => 10,
                'type'        => 'PERCENTAGE',
                'applies_to'  => 'client_code_only',
                'stackable'   => false,
                'priority'    => 5,
                'apply_order' => null,
                'max_discount' => 500,
            ],
            [
                'name'        => 'Flash Sale',
                'badge'       => 'FLASH',
                'discount'    => 25,
                'type'        => 'PERCENTAGE',
                'applies_to'  => 'all',
                'stackable'   => false,
                'priority'    => 1,
                'apply_order' => null,
                'max_discount' => null,
            ],
            [
                'name'        => 'Bundle Discount',
                'badge'       => 'BUNDLE',
                'discount'    => 50,
                'type'        => 'FIXED_AMOUNT',
                'applies_to'  => 'all',
                'stackable'   => true,
                'priority'    => 20,
                'apply_order' => 'fixed_first',
                'max_discount' => null,
            ],
            [
                'name'        => 'Member Exclusive',
                'badge'       => 'MEMBER',
                'discount'    => 20,
                'type'        => 'PERCENTAGE',
                'applies_to'  => 'client_code_only',
                'stackable'   => true,
                'priority'    => 15,
                'apply_order' => 'percentage_first',
                'max_discount' => 1000,
            ],
        ];

        // Insert promotions and collect models
        $createdPromotions = [];
        foreach ($promotions as $data) {
            $promotion = Promotion::create(array_merge($data, [
                'start_date' => now(),
                'end_date'   => now()->addMonths(3),
                'is_active'  => true,
            ]));
            $createdPromotions[] = $promotion;
        }

        // Attach promotions to random variants
        $variants = Variant::all();
        foreach ($variants as $variant) {
            // 30% chance of having at least one promotion
            if (rand(1, 100) <= 30) {
                // Pick 1 to 3 random promotions
                $promoCount = rand(1, 3);
                $randomPromos = collect($createdPromotions)->random(min($promoCount, count($createdPromotions)));

                // Sync without detaching existing ones
                $variant->promotions()->syncWithoutDetaching($randomPromos->pluck('id')->toArray());
            }
        }
    }
}
