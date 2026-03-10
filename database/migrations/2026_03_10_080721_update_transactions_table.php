<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Transaction type: PAYMENT (default), REFUND (linked to original), MANUAL (cash/admin-entered)
            $table->enum('type', ['PAYMENT', 'REFUND', 'MANUAL'])
                ->default('PAYMENT')
                ->after('method');

            // Self-referential FK using uuid (your actual PK) — refund transactions point to the original
            $table->string('parent_transaction_uuid')->nullable()->after('type');
            $table->foreign('parent_transaction_uuid')
                ->references('uuid')
                ->on('transactions')
                ->nullOnDelete();

            // Extracted from `informations` JSON for indexed searching (backfill separately)
            $table->string('payment_reference')->nullable()->after('payment_url');

            // Admin review tracking (for bulk "mark as reviewed" feature)
            $table->timestamp('reviewed_at')->nullable()->after('payment_reference');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Optional free-text notes for manual overrides (e.g. "Confirmed via bank transfer")
            $table->text('notes')->nullable()->after('reviewed_by');

            // Dispute tracking
            $table->enum('dispute_status', ['OPEN', 'RESOLVED', 'LOST'])->nullable()->after('notes');
            $table->timestamp('dispute_opened_at')->nullable()->after('dispute_status');
            $table->timestamp('dispute_resolved_at')->nullable()->after('dispute_opened_at');

            // --- Performance indexes ---
            $table->index('status');
            $table->index('method');
            $table->index('type');
            $table->index('payment_reference');
            $table->index('reviewed_at');
            $table->index('dispute_status');
            // Composite indexes for common filter combinations
            $table->index(['user_id', 'status']);
            $table->index(['order_uuid', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['parent_transaction_uuid']);
            $table->dropForeign(['reviewed_by']);

            $table->dropIndex(['status']);
            $table->dropIndex(['method']);
            $table->dropIndex(['type']);
            $table->dropIndex(['payment_reference']);
            $table->dropIndex(['reviewed_at']);
            $table->dropIndex(['dispute_status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['order_uuid', 'status']);
            $table->dropIndex(['status', 'created_at']);

            $table->dropColumn([
                'type',
                'parent_transaction_uuid',
                'payment_reference',
                'reviewed_at',
                'reviewed_by',
                'notes',
                'dispute_status',
                'dispute_opened_at',
                'dispute_resolved_at',
            ]);
        });
    }
};