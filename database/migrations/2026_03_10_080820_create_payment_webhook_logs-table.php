<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();

            // Nullable: webhook may arrive before the transaction is created/resolved
            $table->string('transaction_uuid')->nullable();
            $table->foreign('transaction_uuid')
                ->references('uuid')
                ->on('transactions')
                ->nullOnDelete();

            // Denormalized for fast lookup even when transaction_uuid is null
            $table->string('order_uuid')->nullable();

            // Which payment gateway sent this (matches Transaction::method values)
            $table->string('gateway');

            // The event type as reported by the gateway (e.g. "payment.success", "refund.created")
            $table->string('event_type')->nullable();

            // Raw inbound payload from the gateway
            $table->json('payload');

            // Our system's response back to the gateway (HTTP status code + body)
            $table->json('response')->nullable();

            // Whether we processed this successfully, are retrying, or it failed
            $table->enum('status', ['RECEIVED', 'PROCESSED', 'FAILED', 'IGNORED'])
                ->default('RECEIVED');

            // Exception message if processing failed
            $table->text('error_message')->nullable();

            // IP address of the callback origin (for security auditing)
            $table->string('source_ip', 45)->nullable();

            $table->timestamps();

            $table->index('transaction_uuid');
            $table->index('order_uuid');
            $table->index('gateway');
            $table->index('status');
            $table->index('created_at');
            $table->index(['gateway', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};
