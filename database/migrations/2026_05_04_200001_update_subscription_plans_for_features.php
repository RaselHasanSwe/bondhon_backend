<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Duration: qty + unit instead of fixed days
            $table->unsignedSmallInteger('duration_qty')->default(30)->after('price_bdt');
            $table->string('duration_unit', 10)->default('day')->after('duration_qty');
        });

        // Copy existing duration_days → duration_qty (day unit)
        DB::statement("UPDATE subscription_plans SET duration_qty = duration_days, duration_unit = 'day'");

        // Change plan_type from enum to varchar for flexible tier names
        try {
            DB::statement("ALTER TABLE subscription_plans MODIFY COLUMN plan_type VARCHAR(50) NOT NULL DEFAULT 'free'");
        } catch (\Throwable $e) {
            // SQLite (tests) does not support MODIFY COLUMN — safe to ignore
        }
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['duration_qty', 'duration_unit']);
        });

        try {
            DB::statement("ALTER TABLE subscription_plans MODIFY COLUMN plan_type ENUM('silver','gold','platinum') NOT NULL");
        } catch (\Throwable $e) {
            // SQLite — ignore
        }
    }
};

