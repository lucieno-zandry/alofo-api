<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Who the promotion applies to
            $table->string('applies_to', 20)->default('all')->after('is_active')
                ->comment('all, client_code_only, regular_only');

            // Stacking control
            $table->boolean('stackable')->default(true)->after('applies_to');
            $table->unsignedSmallInteger('priority')->default(0)->after('stackable')
                ->comment('Lower number = higher priority (used when stackable = false)');

            // Order of application when stacking (percentage before fixed is typical)
            $table->enum('apply_order', ['percentage_first', 'fixed_first'])
                ->nullable()->after('priority')
                ->comment('Order when applying multiple stackable promotions');

            // Optional maximum discount (absolute value) to prevent over‑discounting
            $table->decimal('max_discount', 10, 2)->nullable()->after('apply_order');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'applies_to',
                'stackable',
                'priority',
                'apply_order',
                'max_discount',
            ]);
        });
    }
};
