<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // database/migrations/xxxx_add_dashboard_indexes.php

        Schema::table('transactions', function (Blueprint $table) {
            // For total revenue query: type + status + amount
            $table->index(['type', 'status', 'amount']);
        });

        Schema::table('refund_requests', function (Blueprint $table) {
            // For pending refund count
            $table->index('status');
        });

        // Order table already indexes primary key; if soft deletes are used,
        // ensure a composite index on deleted_at + id if you query frequently.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
