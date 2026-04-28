<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->json('score_breakdown')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            $table->unique(['user_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_scores');
    }
};
