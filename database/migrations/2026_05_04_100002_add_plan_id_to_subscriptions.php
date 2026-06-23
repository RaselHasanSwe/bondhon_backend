<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Link to the dynamic plan; nullable for legacy rows
            $table->foreignId('subscription_plan_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('subscription_plans')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\SubscriptionPlan::class);
            $table->dropColumn('subscription_plan_id');
        });
    }
};

