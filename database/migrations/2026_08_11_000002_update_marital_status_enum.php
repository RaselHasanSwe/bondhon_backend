<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE profiles MODIFY COLUMN marital_status ENUM('never_married','divorced','widowed','awaiting_divorce','separated') NULL");

        DB::table('profiles')->where('marital_status', 'awaiting_divorce')->update(['marital_status' => 'separated']);

        DB::statement("ALTER TABLE profiles MODIFY COLUMN marital_status ENUM('never_married','widowed','divorced','separated') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE profiles MODIFY COLUMN marital_status ENUM('never_married','divorced','widowed','awaiting_divorce') NULL");
    }
};
