<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tracks which specific subscription is currently "in use" for feature access.
            // Nullable so free-plan users don't need a subscription record.
            $table->unsignedBigInteger('active_subscription_id')->nullable()->after('subscription_expires_at');
        });

        // Back-fill: set active_subscription_id to the most-recent active subscription per user
        DB::statement("
            UPDATE users u
            JOIN (
                SELECT user_id, id
                FROM subscriptions
                WHERE status = 'active'
                  AND expires_at > NOW()
                ORDER BY expires_at DESC
            ) s ON s.user_id = u.id
            SET u.active_subscription_id = s.id
            WHERE u.active_subscription_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('active_subscription_id');
        });
    }
};

