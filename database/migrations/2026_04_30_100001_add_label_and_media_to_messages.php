<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add label (caption) column to messages
        Schema::table('messages', function (Blueprint $table) {
            $table->string('label', 500)->nullable()->after('body');
        });

        // Create message_media table for multiple files per message
        Schema::create('message_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();        // bytes
            $table->string('file_mime_type')->nullable();               // e.g. image/jpeg
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_media');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};

