<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds full-text indexes for Laravel Scout database driver.
     */
    public function up(): void
    {
        // Full-text indexes are only supported in MySQL/MariaDB
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->fullText(['about_me', 'city', 'state', 'country']);
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropFullText(['about_me', 'city', 'state', 'country']);
        });
    }
};

