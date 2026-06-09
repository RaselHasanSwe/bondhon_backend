<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            if (Schema::hasColumn('partner_preferences', 'city')) {
                $table->dropColumn('city');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            if (! Schema::hasColumn('partner_preferences', 'city')) {
                $table->json('city')->nullable()->after('country');
            }
        });
    }
};

