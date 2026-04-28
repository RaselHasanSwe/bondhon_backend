<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_careers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('highest_education')->nullable();
            $table->string('college_university')->nullable();
            $table->string('profession')->nullable();
            $table->enum('employed_in', ['private', 'government', 'business', 'self_employed', 'not_working'])->nullable();
            $table->integer('annual_income_bdt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_careers');
    }
};
