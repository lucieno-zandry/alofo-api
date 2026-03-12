<?php

use App\Enums\DiscountType;
use App\Models\Promotion;
use App\Models\Variant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->float('discount');
            $table->enum('type', DiscountType::toArray())->default('percentage');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_active')->default(true);

            // Who the promotion applies to
            $table->string('applies_to', 20)->default('all')
                ->comment('all, client_code_only, regular_only');

            // Stacking control
            $table->boolean('stackable')->default(true);
            $table->unsignedSmallInteger('priority')->default(0)
                ->comment('Lower number = higher priority (used when stackable = false)');

            // Order of application when stacking (percentage before fixed is typical)
            $table->enum('apply_order', ['percentage_first', 'fixed_first'])
                ->nullable()
                ->comment('Order when applying multiple stackable promotions');

            // Optional maximum discount (absolute value) to prevent over‑discounting
            $table->decimal('max_discount', 10, 2)->nullable();

            $table->string('name')->default('');
            $table->string('badge', 50)->nullable()
                ->comment('Short identifier for frontend badge styling (e.g., PARTNER, SALE)');
        });

        Schema::create('promotion_variant', function (Blueprint $table) {
            $table->foreignIdFor(Variant::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Promotion::class)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('promotion_variant');
    }
};
