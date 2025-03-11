<?php

use App\Models\Promotion;
use App\Models\User;
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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->smallInteger('count');
            $table->foreignUuid('order_uuid')->nullable();
            // $table->foreignId('order_id')->nullable();
            $table->foreignIdFor(Variant::class);
            $table->foreignIdFor(Promotion::class)->nullable();
            $table->float('promotion_discount_applied')->nullable();
            $table->float('total');
            $table->foreignIdFor(User::class);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
