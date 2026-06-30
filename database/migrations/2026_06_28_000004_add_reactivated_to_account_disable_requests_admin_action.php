<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE account_disable_requests MODIFY admin_action ENUM('disabled', 'banned', 'reactivated') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE account_disable_requests MODIFY admin_action ENUM('disabled', 'banned') NULL");
    }
};
