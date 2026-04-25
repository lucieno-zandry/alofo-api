<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LandingBlockSeeder extends Seeder
{
    public function run(): void
    {
        $raw = json_decode(file_get_contents(database_path('data/landing_blocks.json')), true);

        // Find the table object that holds the data
        $table = collect($raw)->first(fn($item) => ($item['type'] ?? '') === 'table');

        if (!$table || empty($table['data'])) {
            $this->command?->warn('No table data found in JSON.');
            return;
        }

        foreach ($table['data'] as $row) {
            DB::table('landing_blocks')->updateOrInsert(
                ['id' => $row['id']],
                $row
            );
        }
    }
}
