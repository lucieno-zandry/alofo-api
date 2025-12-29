<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(10)), // random coupon code
            'type' => $this->faker->randomElement(['FIXED_AMOUNT', 'PERCENTAGE']), // discount type
            'discount' => $this->faker->numberBetween(5, 50), // discount value
            'min_order_value' => $this->faker->numberBetween(1000, 10000), // minimum order
            'max_uses' => $this->faker->numberBetween(1, 100), // total allowed uses
            'uses_count' => 0, // start at zero
            'start_date' => $this->faker->dateTimeBetween('now', '+1 week'),
            'end_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'is_active' => true,
        ];
    }
}
