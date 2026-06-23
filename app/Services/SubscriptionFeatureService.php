<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * SubscriptionFeatureService
 *
 * Single source of truth for all subscription feature definitions and
 * per-user feature access resolution.
 *
 * Usage:
 *   $svc = app(SubscriptionFeatureService::class);
 *   $svc->can($user, 'chat_access')          → bool
 *   $svc->value($user, 'daily_matches')       → int|bool|string
 *   $svc->withinDailyLimit($user, 'send_interest_per_day', $usedToday) → bool
 *   $svc->withinMonthlyLimit($user, 'contact_info_views_per_month', $usedMonth) → bool
 */
class SubscriptionFeatureService
{
    // -----------------------------------------------------------------------
    // Feature Definitions
    // -----------------------------------------------------------------------

    /**
     * Complete definition of every subscription feature.
     *
     * Types:
     *  - bool   : on/off toggle
     *  - qty    : numeric limit (0 = disabled)
     *  - enum   : one of a fixed set of string values
     *
     * 'period' (for qty): 'day' | 'month' | null (no reset)
     * 'default' : value for free/no-plan users
     *
     * @return array<string, array{type: string, default: mixed, label: string, group: string, period?: string|null, options?: string[]}>
     */
    public static function definitions(): array
    {
        return [
            // ── Discovery & Search ──────────────────────────────────────────
            'daily_matches' => [
                'type'    => 'qty',
                'period'  => 'day',
                'default' => 5,
                'label'   => 'Daily Match Suggestions',
                'group'   => 'Discovery & Search',
            ],
            'search_access' => [
                'type'    => 'bool',
                'default' => true,
                'label'   => 'Search Access',
                'group'   => 'Discovery & Search',
            ],
            'profile_views_per_day' => [
                'type'    => 'qty',
                'period'  => 'day',
                'default' => 10,
                'label'   => 'Profile Views per Day',
                'group'   => 'Discovery & Search',
            ],
            'search_filters_advanced' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Advanced Search Filters (income, caste, etc.)',
                'group'   => 'Discovery & Search',
            ],
            'profile_id_search' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Search by BON-XXXXXX Profile ID',
                'group'   => 'Discovery & Search',
            ],

            // ── Communication ───────────────────────────────────────────────
            'send_interest_per_day' => [
                'type'    => 'qty',
                'period'  => 'day',
                'default' => 3,
                'label'   => 'Interests Sent per Day',
                'group'   => 'Communication',
            ],
            'chat_access' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Chat Access',
                'group'   => 'Communication',
            ],
            'audio_call_access' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Audio Call Access',
                'group'   => 'Communication',
            ],
            'video_call_access' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Video Call Access',
                'group'   => 'Communication',
            ],
            'message_read_receipt' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Message Read Receipts',
                'group'   => 'Communication',
            ],
            'voice_message_access' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Voice Messages in Chat',
                'group'   => 'Communication',
            ],

            // ── Visibility & Insights ────────────────────────────────────────
            'contact_info_views_per_month' => [
                'type'    => 'qty',
                'period'  => 'month',
                'default' => 0,
                'label'   => 'Contact Info Unlocks per Month',
                'group'   => 'Visibility & Insights',
            ],
            'see_who_liked_me' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'See Who Sent You Interest',
                'group'   => 'Visibility & Insights',
            ],
            'see_who_viewed_profile' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'See Who Viewed Your Profile',
                'group'   => 'Visibility & Insights',
            ],
            'profile_visitors_detailed' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Detailed Visitor List (name, not just count)',
                'group'   => 'Visibility & Insights',
            ],

            // ── Profile Promotion ────────────────────────────────────────────
            'profile_boost_per_month' => [
                'type'    => 'qty',
                'period'  => 'month',
                'default' => 0,
                'label'   => 'Profile Boosts per Month',
                'group'   => 'Profile Promotion',
            ],
            'featured_profile' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Appear in "Featured Profiles"',
                'group'   => 'Profile Promotion',
            ],
            'highlighted_in_search' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Highlighted Badge in Search Results',
                'group'   => 'Profile Promotion',
            ],
            'top_of_match_list' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Appear First in Others\' Daily Matches',
                'group'   => 'Profile Promotion',
            ],

            // ── Photos & Privacy ─────────────────────────────────────────────
            'max_photos_upload' => [
                'type'    => 'qty',
                'period'  => null,
                'default' => 3,
                'label'   => 'Max Photos Allowed to Upload',
                'group'   => 'Photos & Privacy',
            ],
            'private_photo_access' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Send / Receive Private Photo Requests',
                'group'   => 'Photos & Privacy',
            ],
            'photo_request_per_day' => [
                'type'    => 'qty',
                'period'  => 'day',
                'default' => 0,
                'label'   => 'Private Photo Unlock Requests per Day',
                'group'   => 'Photos & Privacy',
            ],
            'profile_visibility_control' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Control Who Sees Your Profile',
                'group'   => 'Photos & Privacy',
            ],

            // ── Trust & Verification ─────────────────────────────────────────
            'verified_badge_eligible' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Can Apply for Verified Badge',
                'group'   => 'Trust & Verification',
            ],
            'priority_verification' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Priority NID/Photo Verification',
                'group'   => 'Trust & Verification',
            ],

            // ── Reports & Analytics ──────────────────────────────────────────
            'compatibility_score_visible' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'See Match % Compatibility Score',
                'group'   => 'Reports & Analytics',
            ],
            'profile_completion_tips' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'AI Profile Completion Tips',
                'group'   => 'Reports & Analytics',
            ],
            'match_report_monthly' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Monthly Match Analysis Report',
                'group'   => 'Reports & Analytics',
            ],

            // ── Support ──────────────────────────────────────────────────────
            'priority_support' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Priority Customer Support',
                'group'   => 'Support',
            ],
            'relationship_advisor' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'Access to Matrimony Advisor',
                'group'   => 'Support',
            ],

            // ── Notifications ────────────────────────────────────────────────
            'email_digest_frequency' => [
                'type'    => 'enum',
                'options' => ['none', 'weekly', 'daily'],
                'default' => 'none',
                'label'   => 'Email Digest Frequency',
                'group'   => 'Notifications',
            ],
            'push_notifications' => [
                'type'    => 'bool',
                'default' => true,
                'label'   => 'Browser / App Push Notifications',
                'group'   => 'Notifications',
            ],
            'sms_notifications' => [
                'type'    => 'bool',
                'default' => false,
                'label'   => 'SMS Alerts',
                'group'   => 'Notifications',
            ],
        ];
    }

    /**
     * Return all feature definitions grouped by 'group'.
     *
     * @return array<string, array<string, array>>
     */
    public static function groupedDefinitions(): array
    {
        $groups = [];
        foreach (self::definitions() as $key => $def) {
            $groups[$def['group']][$key] = $def;
        }
        return $groups;
    }

    // -----------------------------------------------------------------------
    // Runtime Helpers
    // -----------------------------------------------------------------------

    /**
     * Get the active plan features for the user (with defaults for unset keys).
     *
     * @return array<string, mixed>
     */
    public function getPlanFeatures(User $user): array
    {
        $defs    = self::definitions();
        $plan    = $this->getActivePlan($user);
        $stored  = $plan ? ($plan->features ?? []) : [];

        $resolved = [];
        foreach ($defs as $key => $def) {
            $resolved[$key] = array_key_exists($key, $stored) ? $stored[$key] : $def['default'];
        }

        return $resolved;
    }

    /**
     * Get a single feature value for the user.
     */
    public function value(User $user, string $key): mixed
    {
        $plan   = $this->getActivePlan($user);
        $stored = $plan ? ($plan->features ?? []) : [];
        $defs   = self::definitions();

        if (array_key_exists($key, $stored)) {
            return $stored[$key];
        }

        return $defs[$key]['default'] ?? null;
    }

    /**
     * Check boolean feature access.
     */
    public function can(User $user, string $key): bool
    {
        return (bool) $this->value($user, $key);
    }

    /**
     * Check if the user is within a daily usage limit.
     *
     * @param int $usedToday  — count of actions taken today
     */
    public function withinDailyLimit(User $user, string $key, int $usedToday): bool
    {
        $limit = (int) $this->value($user, $key);

        // 0 means disabled (unlimited = -1 or a very large number per your convention)
        // Convention: 0 = blocked, positive = limit, -1 = unlimited
        if ($limit < 0) {
            return true; // unlimited
        }

        return $usedToday < $limit;
    }

    /**
     * Check if the user is within a monthly usage limit.
     *
     * @param int $usedThisMonth
     */
    public function withinMonthlyLimit(User $user, string $key, int $usedThisMonth): bool
    {
        $limit = (int) $this->value($user, $key);

        if ($limit < 0) {
            return true;
        }

        return $usedThisMonth < $limit;
    }

    /**
     * Get the active subscription plan for the user.
     * Uses active_subscription_id if set (precise), falls back to latest active sub.
     */
    public function getActivePlan(User $user): ?SubscriptionPlan
    {
        // Fast path: no subscription at all
        if (! $user->subscription_expires_at || $user->subscription_expires_at->isPast()) {
            return null;
        }

        return Cache::remember(
            "user_plan:{$user->id}",
            now()->addMinutes(5),
            function () use ($user) {
                // Use active_subscription_id if set (preferred — exact plan)
                if ($user->active_subscription_id) {
                    $sub = \App\Models\Subscription::with('subscriptionPlan')
                        ->where('id', $user->active_subscription_id)
                        ->where('user_id', $user->id)
                        ->where('status', 'active')
                        ->where('expires_at', '>', now())
                        ->first();

                    if ($sub?->subscriptionPlan) {
                        return $sub->subscriptionPlan;
                    }
                }

                // Fallback: latest active subscription
                return \App\Models\Subscription::with('subscriptionPlan')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->where('expires_at', '>', now())
                    ->latest('expires_at')
                    ->first()
                    ?->subscriptionPlan;
            }
        );
    }
}

