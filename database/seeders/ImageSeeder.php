<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $raw = json_decode(file_get_contents(database_path('data/images.json')), true);

        // Find the table object that holds the data
        $table = collect($raw)->first(fn($item) => ($item['type'] ?? '') === 'table');

        if (!$table || empty($table['data'])) {
            $this->command?->warn('No table data found in JSON.');
            return;
        }

        foreach ($table['data'] as $row) {
            DB::table('images')->updateOrInsert(
                ['id' => $row['id']],
                $row
            );
        }
    }
}
