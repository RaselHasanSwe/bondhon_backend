<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifestyles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('diet', ['vegetarian', 'non_vegetarian', 'vegan', 'jain'])->nullable();
            $table->enum('smoking', ['non_smoker', 'smoker', 'occasionally'])->nullable();
            $table->enum('drinking', ['non_drinker', 'drinker', 'occasionally'])->nullable();
            $table->json('hobbies')->nullable();
            $table->json('languages_known')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifestyles');
    }
};
