<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                                    // e.g. "Silver Monthly"
            $table->string('slug')->unique();                                          // e.g. "silver-monthly"
            $table->text('description')->nullable();
            $table->enum('plan_type', ['silver', 'gold', 'platinum']);                 // maps to users.subscription_plan
            $table->unsignedInteger('price_bdt');                                      // price in BDT (taka only)
            $table->unsignedSmallInteger('duration_days')->default(30);               // subscription duration
            $table->json('features');                                                  // array of feature strings
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};

