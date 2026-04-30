<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track typing events persistence is not needed (real-time only),
        // but we need conversation unread_count per user for efficiency
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedInteger('user_one_unread')->default(0)->after('last_message_at');
            $table->unsignedInteger('user_two_unread')->default(0)->after('user_one_unread');
            $table->foreignId('last_message_id')->nullable()->after('user_two_unread')
                  ->constrained('messages')->nullOnDelete();
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['last_message_id']);
            $table->dropIndex(['last_message_at']);
            $table->dropColumn(['user_one_unread', 'user_two_unread', 'last_message_id']);
        });
    }
};

