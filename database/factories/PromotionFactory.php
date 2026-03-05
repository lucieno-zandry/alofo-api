<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['PERCENTAGE', 'FIXED_AMOUNT']);
        $discount = $type === 'PERCENTAGE'
            ? $this->faker->numberBetween(5, 30)      // 5% to 30%
            : $this->faker->numberBetween(10, 200);   // fixed amount

        $appliesTo = $this->faker->randomElement(['all', 'client_code_only', 'regular_only']);

        return [
            'name'         => $this->faker->words(3, true),
            'badge'        => $this->faker->optional(0.7)->randomElement(['NEW', 'SALE', 'HOT', 'LIMITED', 'PARTNER']),
            'discount'     => $discount,
            'type'         => $type,
            'start_date'   => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'end_date'     => $this->faker->dateTimeBetween('+1 month', '+3 months'),
            'is_active'    => $this->faker->boolean(90),
            'applies_to'   => $appliesTo,
            'stackable'    => $this->faker->boolean(70),
            'priority'     => $this->faker->numberBetween(0, 100),
            'apply_order'  => $this->faker->optional(0.5)->randomElement(['percentage_first', 'fixed_first']),
            'max_discount' => $this->faker->optional(0.3)->numberBetween(50, 500),
        ];
    }
}
