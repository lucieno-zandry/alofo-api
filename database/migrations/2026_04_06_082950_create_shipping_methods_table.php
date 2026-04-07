<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('carrier', ['custom', 'fedex', 'colissimo'])->default('custom');
            $table->boolean('is_active')->default(true);
            $table->enum('calculation_type', ['flat_rate', 'weight_based', 'api'])->default('flat_rate');
            $table->decimal('flat_rate', 10, 2)->nullable();
            $table->decimal('free_shipping_threshold', 10, 2)->nullable();
            $table->decimal('rate_per_kg', 10, 2)->nullable();
            $table->json('api_config')->nullable();
            $table->integer('min_delivery_days')->nullable();
            $table->integer('max_delivery_days')->nullable();
            $table->json('allowed_countries')->nullable(); // array of ISO codes
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_methods');
    }
};
