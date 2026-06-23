<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Stores values for admin-added custom option groups
            // Format: {"custom_tribe": "sunni", "custom_caste_extended": "other"}
            $table->json('custom_fields')->nullable()->after('what_looking_for');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
    }
};

