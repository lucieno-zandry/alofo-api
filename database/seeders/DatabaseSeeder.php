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

        $this->call(ProductSeeder::class);
        $this->call(CouponSeeder::class);
    }
}
