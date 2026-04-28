<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('age_min')->nullable();
            $table->integer('age_max')->nullable();
            $table->integer('height_min_cm')->nullable();
            $table->integer('height_max_cm')->nullable();
            $table->json('marital_status')->nullable();
            $table->json('religion')->nullable();
            $table->json('caste')->nullable();
            $table->json('education')->nullable();
            $table->json('profession')->nullable();
            $table->integer('income_min_bdt')->nullable();
            $table->integer('income_max_bdt')->nullable();
            $table->json('country')->nullable();
            $table->json('city')->nullable();
            $table->json('diet')->nullable();
            $table->boolean('smoking_acceptable')->default(true);
            $table->boolean('drinking_acceptable')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_preferences');
    }
};
