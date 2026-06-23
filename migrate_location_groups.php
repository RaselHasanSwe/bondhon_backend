<?php
// Run with: php artisan tinker < migrate_location_groups.php

use App\Models\SelectOption;
use App\Models\OptionGroupConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

DB::transaction(function () {
    // 1. Move bd_division rows into country group
    // (parent_id already points to Bangladesh id=238)
    $div = SelectOption::where('group_key', 'bd_division')->update(['group_key' => 'country']);
    echo "Moved bd_division rows: $div\n";

    // 2. Move bd_district rows into country group
    // (parent_id already points to division ids which are now also in country group)
    $dist = SelectOption::where('group_key', 'bd_district')->update(['group_key' => 'country']);
    echo "Moved bd_district rows: $dist\n";

    // 3. Update country OptionGroupConfig to self-nested, depth 5
    OptionGroupConfig::where('group_key', 'country')->update([
        'parent_group_key'  => 'country',
        'max_nesting_depth' => 5,
    ]);
    echo "Updated country config to self-nested (depth 5)\n";

    // 4. Remove now-obsolete group configs
    OptionGroupConfig::whereIn('group_key', ['bd_division', 'bd_district'])->delete();
    echo "Deleted bd_division / bd_district configs\n";
});

Cache::flush();
echo "Cache flushed\n";

// Verify
echo "\n=== RESULTS ===\n";
echo "country total: "    . SelectOption::where('group_key', 'country')->count() . "\n";
echo "country root: "     . SelectOption::where('group_key', 'country')->whereNull('parent_id')->count() . "\n";
echo "country level2: "   . SelectOption::where('group_key', 'country')->whereNotNull('parent_id')
        ->whereHas('parent', fn($q) => $q->where('group_key','country')->whereNull('parent_id'))
        ->count() . "\n";
echo "country level3: "   . SelectOption::where('group_key', 'country')->whereNotNull('parent_id')
        ->whereHas('parent', fn($q) => $q->where('group_key','country')->whereNotNull('parent_id'))
        ->count() . "\n";
echo "bd_division left: " . SelectOption::where('group_key', 'bd_division')->count() . "\n";
echo "bd_district left: " . SelectOption::where('group_key', 'bd_district')->count() . "\n";
$cfg = OptionGroupConfig::where('group_key', 'country')->first();
echo "country config: parent_group_key={$cfg->parent_group_key} max_depth={$cfg->max_nesting_depth}\n";

