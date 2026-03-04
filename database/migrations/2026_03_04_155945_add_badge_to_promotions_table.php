<?php
// database/migrations/2025_01_01_000004_add_badge_to_promotions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('name')->after('id')->default('');
            $table->string('badge', 50)->nullable()->after('name')
                ->comment('Short identifier for frontend badge styling (e.g., PARTNER, SALE)');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('badge');
        });
    }
};
