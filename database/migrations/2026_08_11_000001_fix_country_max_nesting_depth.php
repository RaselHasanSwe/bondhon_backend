<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('option_group_configs')
            ->where('group_key', 'country')
            ->update(['max_nesting_depth' => 4]);
    }

    public function down(): void
    {
        DB::table('option_group_configs')
            ->where('group_key', 'country')
            ->update(['max_nesting_depth' => 5]);
    }
};
