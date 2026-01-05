<?php

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
        Schema::create('transactions', function (Blueprint $table) {
            // $table->id();
            $table->uuid()->primary();
            $table->timestamps();
            $table->enum('status', ['FAILED', 'PENDING', 'SUCCESS'])->default('PENDING');
            $table->json('informations')->nullable();
            $table->foreignIdFor(User::class);
            $table->foreignUuid('order_uuid');
            $table->softDeletes();
            $table->enum('method', ['VISA', 'MASTERCARD', 'ORANGEMONEY', 'AIRTELMONEY', 'MVOLA', 'PAYPAL']);
            $table->text('payment_url')->nullable();
            $table->float('amount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
