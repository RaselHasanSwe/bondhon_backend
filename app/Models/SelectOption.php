<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelectOption extends Model
{
    protected $fillable = [
        'group_key', 'parent_id', 'value',
        'label', 'metadata', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata'  => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SelectOption::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SelectOption::class, 'parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeGroup($q, string $groupKey)
    {
        return $q->where('group_key', $groupKey);
    }

    public function scopeTopLevel($q)
    {
        return $q->whereNull('parent_id');
    }

    public function scopeChildrenOf($q, int $parentId)
    {
        return $q->where('parent_id', $parentId);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public static function optionsFor(string $groupKey, ?int $parentId = null): array
    {
        return static::active()
            ->group($groupKey)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get(['value', 'label'])
            ->toArray();
    }
}

