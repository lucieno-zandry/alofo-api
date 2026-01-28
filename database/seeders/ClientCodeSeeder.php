<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [];

        for ($i = 0; $i < 10; $i++) {
            $maxUses = rand(1, 20);

            $codes[] = [
                'code' => strtoupper(Str::random(8)),
                'is_active' => rand(0, 1),
                'max_uses' => $maxUses,
                'uses' => rand(0, $maxUses),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('client_codes')->insert($codes);
    }
}
