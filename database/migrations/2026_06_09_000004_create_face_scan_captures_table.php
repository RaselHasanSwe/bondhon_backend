<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_scan_captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('face_scan_session_id')->constrained('face_scan_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('capture_key', 50);
            $table->string('image_path');
            $table->json('metadata')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->unique(['face_scan_session_id', 'capture_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_scan_captures');
    }
};

