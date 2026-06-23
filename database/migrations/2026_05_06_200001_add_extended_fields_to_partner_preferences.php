<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            // Physical appearance preferences
            $table->json('body_type')->nullable()->after('diet');
            $table->json('complexion')->nullable()->after('body_type');
            $table->json('blood_group')->nullable()->after('complexion');

            // Linguistic / identity
            $table->json('mother_tongue')->nullable()->after('blood_group');

            // Religious / spiritual preferences
            $table->json('manglik_status')->nullable()->after('mother_tongue');
            $table->json('rashi')->nullable()->after('manglik_status');
            $table->json('religiousness')->nullable()->after('rashi');
            $table->json('pray')->nullable()->after('religiousness');

            // Family preferences
            $table->string('has_children', 10)->nullable()->after('pray');   // no | yes | any
            $table->json('child_living_status')->nullable()->after('has_children');
            $table->json('family_type')->nullable()->after('child_living_status');
            $table->json('family_values')->nullable()->after('family_type');

            // Career / employment preferences
            $table->json('working_status')->nullable()->after('family_values');
            $table->json('employed_in')->nullable()->after('working_status');

            // Location / residency preference
            $table->json('pref_residing_status')->nullable()->after('employed_in');
        });
    }

    public function down(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'body_type', 'complexion', 'blood_group',
                'mother_tongue',
                'manglik_status', 'rashi', 'religiousness', 'pray',
                'has_children', 'child_living_status', 'family_type', 'family_values',
                'working_status', 'employed_in',
                'pref_residing_status',
            ]);
        });
    }
};

