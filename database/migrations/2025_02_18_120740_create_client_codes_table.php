<?php

use App\Models\ClientCode;
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
        Schema::create('client_codes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('max_uses')->default(999999);
            $table->integer('uses')->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(ClientCode::class)->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_codes');
        Schema::dropColumns('users', 'client_code_id');
    }
};
