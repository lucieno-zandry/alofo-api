<?php

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            // $table->id();
            $table->uuid()->primary();
            $table->timestamps();
            $table->float('total');
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Address::class);
            $table->foreignId('coupon_id')->nullable();
            $table->float('coupon_discount_applied')->nullable();
            $table->softDeletes();
            $table->json('address_snapshot');
            $table->json('coupon_snapshot')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
