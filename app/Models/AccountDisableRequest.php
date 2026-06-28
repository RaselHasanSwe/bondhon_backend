<?php

namespace App\Models;

use App\Enums\AccountDisableAdminAction;
use App\Enums\AccountDisableRequestStatus;
use App\Enums\AccountDisableRequestType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDisableRequest extends Model
{
    protected $fillable = [
        'user_id',
        'request_type',
        'message',
        'status',
        'admin_action',
        'admin_message',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => AccountDisableRequestType::class,
            'status'       => AccountDisableRequestStatus::class,
            'admin_action' => AccountDisableAdminAction::class,
            'reviewed_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AccountDisableRequestStatus::Pending);
    }

    public function isPending(): bool
    {
        return $this->status === AccountDisableRequestStatus::Pending;
    }

    public function canReactivate(): bool
    {
        if ($this->status !== AccountDisableRequestStatus::ActionTaken) {
            return false;
        }

        if ($this->admin_action === AccountDisableAdminAction::Reactivated) {
            return false;
        }

        $user = $this->user;

        return $user && ($user->is_banned || ! $user->is_active);
    }

    public function isReactivated(): bool
    {
        return $this->admin_action === AccountDisableAdminAction::Reactivated;
    }
}
