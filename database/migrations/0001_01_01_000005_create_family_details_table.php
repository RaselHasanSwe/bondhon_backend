<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('family_type', ['joint', 'nuclear', 'extended'])->nullable();
            $table->enum('family_status', ['middle_class', 'upper_middle_class', 'rich', 'affluent'])->nullable();
            $table->integer('family_income_bdt_per_month')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->integer('brothers_count')->default(0);
            $table->integer('sisters_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_details');
    }
};
