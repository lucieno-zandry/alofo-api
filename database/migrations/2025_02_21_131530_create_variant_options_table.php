<?php

use App\Models\Variant;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('variant_options', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('value');
            $table->foreignIdFor(VariantGroup::class)->constrained()->cascadeOnDelete();
            $table->unique(['variant_group_id', 'value']);
            $table->fullText(['value']);
        });

        Schema::create('variant_variant_option', function (Blueprint $table) {
            $table->foreignIdFor(Variant::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(VariantOption::class)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_options');
        Schema::dropIfExists('variant_variant_option');
    }
};
