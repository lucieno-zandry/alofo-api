<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('block_type', 50);          // hero, collection_grid, featured_products, story, etc.
            $table->string('title', 255)->nullable();
            $table->text('subtitle')->nullable();
            $table->jsonb('content')->nullable();      // JSONB for flexible data (PostgreSQL)
            // Polymorphic fields
            $table->string('landing_able_type', 50)->nullable();
            $table->unsignedInteger('landing_able_id')->nullable();
            // Common direct reference for images (optional, but convenient)
            $table->foreignId('image_id')
                ->nullable()
                ->constrained('images')
                ->onDelete('set null');
            // Ordering and status
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();  // created_at, updated_at (already timestamp with time zone in PostgreSQL)

            // Index for polymorphic lookups
            $table->index(['landing_able_type', 'landing_able_id']);
            $table->index('block_type');
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_blocks');
    }
};
