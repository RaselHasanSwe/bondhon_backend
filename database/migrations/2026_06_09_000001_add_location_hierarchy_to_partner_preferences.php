<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            // Location hierarchy preferences for Bangladesh
            $table->json('pref_divisions')->nullable()->after('country');
            $table->json('pref_districts')->nullable()->after('pref_divisions');

            // Location hierarchy preferences for Canada
            $table->json('pref_provinces')->nullable()->after('pref_districts');

            // Location hierarchy preferences for USA
            $table->json('pref_states')->nullable()->after('pref_provinces');
        });
    }

    public function down(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->dropColumn(['pref_divisions', 'pref_districts', 'pref_provinces', 'pref_states']);
        });
    }
};

