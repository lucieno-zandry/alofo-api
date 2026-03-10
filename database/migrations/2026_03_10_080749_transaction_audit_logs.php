<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_audit_logs', function (Blueprint $table) {
            $table->id();

            // References the transaction uuid (your actual PK)
            $table->string('transaction_uuid');
            $table->foreign('transaction_uuid')
                ->references('uuid')
                ->on('transactions')
                ->cascadeOnDelete();

            // The admin/system actor who triggered the change (null = automated/system)
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->foreign('performed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Action type: status_override | refund_initiated | notification_resent |
            //              reviewed | dispute_opened | dispute_resolved | soft_deleted | restored
            $table->string('action');

            // For status changes: what the value was before and after
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();

            // Required free-text explanation for manual actions
            $table->text('reason')->nullable();

            // Snapshot context: IP address, user agent, admin user info, etc.
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('transaction_uuid');
            $table->index('performed_by');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_audit_logs');
    }
};
