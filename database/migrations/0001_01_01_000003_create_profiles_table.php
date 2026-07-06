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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('profile_id')->unique();
            $table->date('dob')->nullable();
            $table->integer('height_cm')->nullable();
            $table->integer('weight_kg')->nullable();
            $table->enum('complexion', ['very_fair', 'fair', 'wheatish', 'dark'])->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])->nullable();
            $table->enum('marital_status', ['never_married', 'divorced', 'widowed', 'awaiting_divorce'])->nullable();
            $table->string('mother_tongue')->nullable();
            $table->string('nationality')->default('Bangladeshi');
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->text('about_me')->nullable();
            $table->integer('profile_completion_percentage')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_photo_approved')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->json('privacy_settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
