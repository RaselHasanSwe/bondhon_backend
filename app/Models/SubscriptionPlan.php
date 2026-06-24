<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\SubscriptionFeatureService;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'plan_type',
        'price_bdt',
        'duration_qty',
        'duration_unit',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'features'      => 'array',
            'is_active'     => 'boolean',
            'price_bdt'     => 'integer',
            'duration_qty'  => 'integer',
            'sort_order'    => 'integer',
        ];
    }

    // -----------------------------------------------------------------------
    // Duration helper
    // -----------------------------------------------------------------------

    /**
     * Convert duration_qty + duration_unit to total days for expiry calculation.
     */
    public function getDurationInDays(): int
    {
        return match ($this->duration_unit) {
            'hour'  => max(1, (int) round(($this->duration_qty ?? 24) / 24)),
            'month' => ($this->duration_qty ?? 1) * 30,
            'year'  => ($this->duration_qty ?? 1) * 365,
            default => $this->duration_qty ?? 30, // 'day' or fallback
        };
    }

    /**
     * Get a specific feature value from this plan.
     * Returns the plan's value or the default from feature definitions.
     */
    public function feature(string $key): mixed
    {
        $features = $this->features ?? [];
        $defs     = SubscriptionFeatureService::definitions();

        if (array_key_exists($key, $features)) {
            return $features[$key];
        }

        return $defs[$key]['default'] ?? null;
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function subscriptionType()
    {
        return $this->belongsTo(SubscriptionType::class, 'plan_type', 'id');
    }
}
