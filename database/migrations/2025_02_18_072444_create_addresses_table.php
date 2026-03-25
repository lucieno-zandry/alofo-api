<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            $table->string('label')->nullable();                     // e.g., "Home", "Work"
            $table->string('recipient_name');                        // instead of fullname
            $table->string('phone');                                 // primary phone
            $table->string('phone_alt')->nullable();                 // secondary phone

            $table->string('line1');                                 // street address
            $table->string('line2')->nullable();                     // apartment, suite, etc.
            $table->string('city');
            $table->string('state')->nullable();                     // state/province/region
            $table->string('postal_code');
            $table->string('country', 2);                            // ISO 3166-1 alpha-2 code

            $table->enum('address_type', ['billing', 'shipping', 'both'])->default('shipping');
            $table->boolean('is_default')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
