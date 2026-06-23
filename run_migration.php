<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SelectOption;
use App\Models\OptionGroupConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

DB::transaction(function() {
    $a = SelectOption::where('group_key','bd_division')->update(['group_key'=>'country']);
    $b = SelectOption::where('group_key','bd_district')->update(['group_key'=>'country']);
    OptionGroupConfig::where('group_key','country')->update(['parent_group_key'=>'country','max_nesting_depth'=>5]);
    OptionGroupConfig::whereIn('group_key',['bd_division','bd_district'])->delete();
    echo "Moved bd_division: $a rows, bd_district: $b rows\n";
    echo "Updated country config to self-nested depth=5\n";
    echo "Deleted bd_division / bd_district configs\n";
});

Cache::flush();
echo "Cache flushed\n\n";

echo "=== VERIFY ===\n";
echo "country total: "  . SelectOption::where('group_key','country')->count() . "\n";
echo "country root: "   . SelectOption::where('group_key','country')->whereNull('parent_id')->count() . "\n";
echo "bd_div left: "    . SelectOption::where('group_key','bd_division')->count() . "\n";
echo "bd_dist left: "   . SelectOption::where('group_key','bd_district')->count() . "\n";
$cfg = OptionGroupConfig::where('group_key','country')->first();
echo "config: parent_group_key={$cfg->parent_group_key} depth={$cfg->max_nesting_depth}\n";

// Sample check - BD divisions under Bangladesh (id 238)
$divs = SelectOption::where('group_key','country')->where('parent_id',238)->pluck('label')->toArray();
echo "BD Divisions under Bangladesh: " . implode(', ', $divs) . "\n";

