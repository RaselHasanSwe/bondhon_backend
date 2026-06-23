<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend ENUM type only on MySQL/MariaDB (SQLite doesn't support MODIFY COLUMN)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `messages` MODIFY COLUMN `type` ENUM('text','image','video','audio','document','voice') NOT NULL DEFAULT 'text'");
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');  // bytes
            $table->string('file_mime_type')->nullable()->after('file_size');         // e.g. application/pdf
            $table->unsignedInteger('duration_seconds')->nullable()->after('file_mime_type'); // for audio/video
            $table->string('thumbnail_path')->nullable()->after('duration_seconds');  // for video
            $table->json('reactions')->nullable()->after('thumbnail_path');            // emoji reactions
            $table->foreignId('reply_to_message_id')->nullable()->after('reactions')
                  ->constrained('messages')->nullOnDelete();
            // Indexes for efficient queries
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_message_id']);
            $table->dropIndex(['conversation_id', 'created_at']);
            $table->dropIndex(['sender_id', 'created_at']);
            $table->dropColumn([
                'file_name', 'file_size', 'file_mime_type',
                'duration_seconds', 'thumbnail_path', 'reactions', 'reply_to_message_id',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `messages` MODIFY COLUMN `type` ENUM('text','image','document','voice') NOT NULL DEFAULT 'text'");
        }
    }
};

