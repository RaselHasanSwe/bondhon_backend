<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use App\Notifications\CustomVerifyEmail;
use App\Notifications\CustomResetPassword;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'profile_created_by',
        'role',
        'is_active',
        'is_banned',
        'subscription_plan',
        'subscription_expires_at',
        'active_subscription_id',
        'email_verified_at'
    ];

    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail());
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'is_banned' => 'boolean',
        ];
    }

    /**
     * Relationships
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Override the database notification model to use our custom casts.
     */
    public function databaseNotificationModel(): string
    {
        return \App\Models\Notification::class;
    }

    public function religiousDetail(): HasOne
    {
        return $this->hasOne(ReligiousDetail::class);
    }

    public function familyDetail(): HasOne
    {
        return $this->hasOne(FamilyDetail::class);
    }

    public function educationCareer(): HasOne
    {
        return $this->hasOne(EducationCareer::class);
    }

    public function lifestyle(): HasOne
    {
        return $this->hasOne(Lifestyle::class);
    }

    public function horoscopeDetail(): HasOne
    {
        return $this->hasOne(HoroscopeDetail::class);
    }

    public function partnerPreference(): HasOne
    {
        return $this->hasOne(PartnerPreference::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class);
    }

    public function sentInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'sender_id');
    }

    public function receivedInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'receiver_id');
    }

    public function profileViews(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewer_id');
    }

    public function viewedBy(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewed_id');
    }

    public function shortlist(): HasMany
    {
        return $this->hasMany(Shortlist::class);
    }

    public function shortlistedBy(): HasMany
    {
        return $this->hasMany(Shortlist::class, 'shortlisted_user_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'blocker_id');
    }

    public function blockedBy(): HasMany
    {
        return $this->hasMany(Block::class, 'blocked_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reportedBy(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_id');
    }

    public function conversationsAsUserOne(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    public function conversationsAsUserTwo(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function initiatedCalls(): HasMany
    {
        return $this->hasMany(CallLog::class, 'caller_id');
    }

    public function receivedCalls(): HasMany
    {
        return $this->hasMany(CallLog::class, 'receiver_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * The subscription currently providing feature access.
     */
    public function activeSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'active_subscription_id');
    }

    public function matchScores(): HasMany
    {
        return $this->hasMany(MatchScore::class);
    }

    /**
     * Laravel Scout — Searchable
     */
    public function searchableAs(): string
    {
        return 'users';
    }

    public function toSearchableArray(): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'gender' => $this->gender,
        ];
    }
}

