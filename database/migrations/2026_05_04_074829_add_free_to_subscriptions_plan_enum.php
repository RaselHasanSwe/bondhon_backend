<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'free' to the subscriptions.plan ENUM so free-tier subscriptions can be stored.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `subscriptions`
            MODIFY COLUMN `plan`
            ENUM('free', 'silver', 'gold', 'platinum') NOT NULL");
    }

    public function down(): void
    {
        // Note: rolling back will fail if any 'free' rows exist — remove them first.
        DB::statement("ALTER TABLE `subscriptions`
            MODIFY COLUMN `plan`
            ENUM('silver', 'gold', 'platinum') NOT NULL");
    }
};
