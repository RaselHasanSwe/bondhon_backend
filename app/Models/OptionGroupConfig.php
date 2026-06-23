<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionGroupConfig extends Model
{
    protected $fillable = [
        'group_key', 'label', 'profile_tab', 'field_name',
        'input_type', 'parent_group_key', 'max_nesting_depth',
        'sort_order', 'is_active', 'is_system',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'is_system'         => 'boolean',
        'max_nesting_depth' => 'integer',
        'sort_order'        => 'integer',
    ];

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForTab($q, string $tab)
    {
        return $q->where('profile_tab', $tab);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /** Is this group self-nesting (its options parent inside the same group)? */
    public function isSelfNested(): bool
    {
        return $this->parent_group_key !== null
            && $this->parent_group_key === $this->group_key;
    }

    /** Is this a cross-group nested group (parents from a different group)? */
    public function isCrossNested(): bool
    {
        return $this->parent_group_key !== null
            && $this->parent_group_key !== $this->group_key;
    }

    /** Get the chain of group configs from root down to this group. */
    public static function getChain(string $groupKey): array
    {
        $chain = [];
        $current = $groupKey;
        $visited = [];

        while ($current && !in_array($current, $visited)) {
            $visited[] = $current;
            $config = static::where('group_key', $current)->first();
            if (!$config) break;

            array_unshift($chain, $config);

            if ($config->parent_group_key && $config->parent_group_key !== $current) {
                $current = $config->parent_group_key;
            } else {
                break;
            }
        }

        return $chain;
    }

    /** Build a flat list of options for a group with depth and ancestry. */
    public static function buildFlatTree(string $groupKey, int $maxDepth = 5): array
    {
        $all = SelectOption::where('group_key', $groupKey)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');

        // Also include parent options if they're from a different group
        $parentOptionsMap = [];
        foreach ($all as $opt) {
            if ($opt->parent_id && !$all->has($opt->parent_id)) {
                $parentOptionsMap[$opt->parent_id] = SelectOption::find($opt->parent_id);
            }
        }

        $rows = [];
        static::buildTreeRecursive($all, $parentOptionsMap, null, 0, $maxDepth, $rows, []);

        return $rows;
    }

    private static function buildTreeRecursive(
        $all,
        array $parentMap,
        ?int $parentId,
        int $depth,
        int $maxDepth,
        array &$rows,
        array $ancestors
    ): void {
        if ($depth >= $maxDepth) return;

        foreach ($all->where('parent_id', $parentId) as $opt) {
            $opt->_depth     = $depth;
            $opt->_ancestors = $ancestors;
            $opt->_path      = implode(' › ', array_merge(
                array_map(fn($a) => $a['label'], $ancestors),
                [$opt->label]
            ));
            $rows[] = $opt;
            static::buildTreeRecursive(
                $all, $parentMap, $opt->id, $depth + 1, $maxDepth, $rows,
                array_merge($ancestors, [['id' => $opt->id, 'label' => $opt->label]])
            );
        }
    }
}

