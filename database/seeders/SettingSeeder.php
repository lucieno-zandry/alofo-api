<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // database/seeders/SettingSeeder.php
        Setting::create([
            'key' => 'maintenance_mode',
            'value' => false,
            'type' => 'boolean',
            'group' => 'Maintenance',
            'label' => 'Maintenance Mode',
            'description' => 'When enabled, the site will be unavailable to non-admin users.',
            'is_public' => true,
        ]);

        Setting::create([
            'key' => 'currency',
            'value' => 'EUR',
            'type' => 'string',
            'group' => 'General',
            'label' => 'Currency',
            'description' => 'Default currency for the store.',
            'is_public' => true,
        ]);
    }
}
