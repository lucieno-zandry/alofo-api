<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var \App\Models\User */
        $user = User::create([
            'name' => 'Lucieno Zandry',
            'email' => 'lucienozandry4@gmail.com',
            'password' => '27092001',
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        $user->statuses()->create([
            'status' => 'approved',
        ]);
    }
}
