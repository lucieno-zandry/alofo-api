<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->json('applied_promotions_snapshot')
                ->nullable()
                ->after('promotion_discount_applied')
                ->comment('Snapshot of promotions that contributed to the final price');

            // Remove the single promotion_id if it exists
            if (Schema::hasColumn('cart_items', 'promotion_id')) {
                $table->dropColumn('promotion_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('applied_promotions_snapshot');
            $table->unsignedBigInteger('promotion_id')->nullable()->after('variant_id');
        });
    }
};
