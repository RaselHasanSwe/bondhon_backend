<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand enum to include new values while keeping legacy ones for data migration.
        DB::statement("ALTER TABLE users MODIFY COLUMN profile_created_by ENUM('self','parents','siblings','relative','friend','other','guardian','relative_and_friends') NULL");

        DB::table('users')->whereIn('profile_created_by', ['friend', 'relative', 'other'])
            ->update(['profile_created_by' => 'relative_and_friends']);

        DB::statement("ALTER TABLE users MODIFY COLUMN profile_created_by ENUM('self','parents','siblings','guardian','relative_and_friends') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN profile_created_by ENUM('self','parents','siblings','relative','friend','other') NULL");
    }
};
