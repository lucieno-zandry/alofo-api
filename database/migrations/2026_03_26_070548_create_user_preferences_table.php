<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('theme')->default('system');   // light, dark, system
            $table->string('language')->default('en');
            $table->string('timezone')->default('UTC');
            $table->string('currency')->default('USD');
            $table->timestamps();

            $table->unique('user_id'); // one-to-one
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
