<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->onDelete('cascade');
            $table->string('country_code', 2)->default('*'); // '*' = default
            $table->string('city_pattern')->nullable();      // optional regex/glob pattern
            $table->decimal('min_weight_kg', 10, 3)->nullable();
            $table->decimal('max_weight_kg', 10, 3)->nullable();
            $table->decimal('rate', 10, 2);
            $table->decimal('rate_per_kg', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['shipping_method_id', 'country_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_rates');
    }
};
