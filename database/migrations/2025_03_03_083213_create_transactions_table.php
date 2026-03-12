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
            $table->string('method');
            $table->text('payment_url')->nullable();
            $table->float('amount')->default(0);

            // Transaction type: PAYMENT (default), REFUND (linked to original), MANUAL (cash/admin-entered)
            $table->enum('type', ['PAYMENT', 'REFUND', 'MANUAL'])
                ->default('PAYMENT');

            // Self-referential FK using uuid (your actual PK) — refund transactions point to the original
            $table->string('parent_transaction_uuid')->nullable();
            $table->foreign('parent_transaction_uuid')
                ->references('uuid')
                ->on('transactions')
                ->nullOnDelete();

            // Extracted from `informations` JSON for indexed searching (backfill separately)
            $table->string('payment_reference')->nullable();

            // Admin review tracking (for bulk "mark as reviewed" feature)
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Optional free-text notes for manual overrides (e.g. "Confirmed via bank transfer")
            $table->text('notes')->nullable();

            // Dispute tracking
            $table->enum('dispute_status', ['OPEN', 'RESOLVED', 'LOST'])->nullable();
            $table->timestamp('dispute_opened_at')->nullable();
            $table->timestamp('dispute_resolved_at')->nullable();
            $table->text('dispute_reason')->nullable();

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
