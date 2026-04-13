<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->after('coupon_id')->constrained()->nullOnDelete();
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('shipping_method_id');
            $table->decimal('total_weight_kg', 10, 3)->nullable()->after('shipping_cost');
            $table->json('shipping_method_snapshot')->nullable()->after('total_weight_kg');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn(['shipping_method_id', 'shipping_cost', 'total_weight_kg', 'shipping_method_snapshot']);
        });
    }
};