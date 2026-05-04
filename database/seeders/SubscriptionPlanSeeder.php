<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * SubscriptionPlanSeeder
 *
 * Seeds all subscription plans across four tiers (Free, Silver, Gold, Platinum)
 * and four durations (1 month, 3 months, 6 months, 1 year).
 *
 * Features are stored as an explicit key → value map so that only permissions
 * intentionally assigned by a superadmin are surfaced in the frontend.
 * (-1 = unlimited, 0 = disabled, positive integer = limit)
 */
class SubscriptionPlanSeeder extends Seeder
{
    // -------------------------------------------------------------------------
    // Feature sets per tier
    // -------------------------------------------------------------------------

    /**
     * Free tier — baseline access, no purchase required.
     * Stored as a plan so the admin panel can display it for reference.
     */
    private static function freeFeatures(): array
    {
        return [
            // Discovery & Search
            'daily_matches'            => 5,
            'search_access'            => true,
            'profile_views_per_day'    => 10,
            'search_filters_advanced'  => false,
            'profile_id_search'        => false,
            // Communication
            'send_interest_per_day'    => 3,
            'chat_access'              => false,
            'audio_call_access'        => false,
            'video_call_access'        => false,
            'message_read_receipt'     => false,
            'voice_message_access'     => false,
            // Visibility & Insights
            'contact_info_views_per_month' => 0,
            'see_who_liked_me'             => false,
            'see_who_viewed_profile'       => false,
            'profile_visitors_detailed'    => false,
            // Profile Promotion
            'profile_boost_per_month'  => 0,
            'featured_profile'         => false,
            'highlighted_in_search'    => false,
            'top_of_match_list'        => false,
            // Photos & Privacy
            'max_photos_upload'           => 3,
            'private_photo_access'        => false,
            'photo_request_per_day'       => 0,
            'profile_visibility_control'  => false,
            // Trust & Verification
            'verified_badge_eligible'  => false,
            'priority_verification'    => false,
            // Reports & Analytics
            'compatibility_score_visible' => false,
            'profile_completion_tips'     => false,
            'match_report_monthly'        => false,
            // Support
            'priority_support'     => false,
            'relationship_advisor' => false,
            // Notifications
            'email_digest_frequency' => 'none',
            'push_notifications'     => true,
            'sms_notifications'      => false,
        ];
    }

    /**
     * Silver tier — unlocks chat, basic contact views, photo management.
     */
    private static function silverFeatures(): array
    {
        return [
            // Discovery & Search
            'daily_matches'            => 20,
            'search_access'            => true,
            'profile_views_per_day'    => -1,   // unlimited
            'search_filters_advanced'  => true,
            'profile_id_search'        => true,
            // Communication
            'send_interest_per_day'    => 20,
            'chat_access'              => true,
            'audio_call_access'        => false,
            'video_call_access'        => false,
            'message_read_receipt'     => true,
            'voice_message_access'     => false,
            // Visibility & Insights
            'contact_info_views_per_month' => 10,
            'see_who_liked_me'             => false,
            'see_who_viewed_profile'       => true,
            'profile_visitors_detailed'    => false,
            // Profile Promotion
            'profile_boost_per_month'  => 0,
            'featured_profile'         => false,
            'highlighted_in_search'    => false,
            'top_of_match_list'        => false,
            // Photos & Privacy
            'max_photos_upload'           => 6,
            'private_photo_access'        => true,
            'photo_request_per_day'       => 3,
            'profile_visibility_control'  => true,
            // Trust & Verification
            'verified_badge_eligible'  => true,
            'priority_verification'    => false,
            // Reports & Analytics
            'compatibility_score_visible' => false,
            'profile_completion_tips'     => true,
            'match_report_monthly'        => false,
            // Support
            'priority_support'     => false,
            'relationship_advisor' => false,
            // Notifications
            'email_digest_frequency' => 'weekly',
            'push_notifications'     => true,
            'sms_notifications'      => false,
        ];
    }

    /**
     * Gold tier — adds audio/video calls, who-liked-me, boost, advanced analytics.
     */
    private static function goldFeatures(): array
    {
        return [
            // Discovery & Search
            'daily_matches'            => 50,
            'search_access'            => true,
            'profile_views_per_day'    => -1,
            'search_filters_advanced'  => true,
            'profile_id_search'        => true,
            // Communication
            'send_interest_per_day'    => 50,
            'chat_access'              => true,
            'audio_call_access'        => true,
            'video_call_access'        => true,
            'message_read_receipt'     => true,
            'voice_message_access'     => true,
            // Visibility & Insights
            'contact_info_views_per_month' => 30,
            'see_who_liked_me'             => true,
            'see_who_viewed_profile'       => true,
            'profile_visitors_detailed'    => true,
            // Profile Promotion
            'profile_boost_per_month'  => 1,
            'featured_profile'         => false,
            'highlighted_in_search'    => true,
            'top_of_match_list'        => false,
            // Photos & Privacy
            'max_photos_upload'           => 12,
            'private_photo_access'        => true,
            'photo_request_per_day'       => 10,
            'profile_visibility_control'  => true,
            // Trust & Verification
            'verified_badge_eligible'  => true,
            'priority_verification'    => true,
            // Reports & Analytics
            'compatibility_score_visible' => true,
            'profile_completion_tips'     => true,
            'match_report_monthly'        => true,
            // Support
            'priority_support'     => false,
            'relationship_advisor' => false,
            // Notifications
            'email_digest_frequency' => 'daily',
            'push_notifications'     => true,
            'sms_notifications'      => true,
        ];
    }

    /**
     * Platinum tier — everything unlimited, full visibility & priority support.
     */
    private static function platinumFeatures(): array
    {
        return [
            // Discovery & Search
            'daily_matches'            => -1,   // unlimited
            'search_access'            => true,
            'profile_views_per_day'    => -1,
            'search_filters_advanced'  => true,
            'profile_id_search'        => true,
            // Communication
            'send_interest_per_day'    => -1,
            'chat_access'              => true,
            'audio_call_access'        => true,
            'video_call_access'        => true,
            'message_read_receipt'     => true,
            'voice_message_access'     => true,
            // Visibility & Insights
            'contact_info_views_per_month' => -1,
            'see_who_liked_me'             => true,
            'see_who_viewed_profile'       => true,
            'profile_visitors_detailed'    => true,
            // Profile Promotion
            'profile_boost_per_month'  => 5,
            'featured_profile'         => true,
            'highlighted_in_search'    => true,
            'top_of_match_list'        => true,
            // Photos & Privacy
            'max_photos_upload'           => 20,
            'private_photo_access'        => true,
            'photo_request_per_day'       => -1,
            'profile_visibility_control'  => true,
            // Trust & Verification
            'verified_badge_eligible'  => true,
            'priority_verification'    => true,
            // Reports & Analytics
            'compatibility_score_visible' => true,
            'profile_completion_tips'     => true,
            'match_report_monthly'        => true,
            // Support
            'priority_support'     => true,
            'relationship_advisor' => true,
            // Notifications
            'email_digest_frequency' => 'daily',
            'push_notifications'     => true,
            'sms_notifications'      => true,
        ];
    }

    // -------------------------------------------------------------------------
    // Seeder
    // -------------------------------------------------------------------------

    public function run(): void
    {
        /*
         * Plan matrix
         * -----------
         * Tiers    : Free | Silver | Gold | Platinum
         * Durations: 1 month | 3 months | 6 months | 1 year
         *
         * Pricing strategy (Silver base = ৳500/mo):
         *   3 months → ~10% off monthly rate
         *   6 months → ~15% off monthly rate
         *   1 year   → ~20% off monthly rate
         *
         * sort_order groups tiers together: Free=0, Silver=10-13, Gold=20-23, Platinum=30-33
         */

        $plans = [

            // ─────────────────────────────────────────────────────────────
            // FREE (auto-assigned to all new users on registration — forever)
            // ─────────────────────────────────────────────────────────────
            [
                'name'          => 'Free',
                'slug'          => 'free',
                'description'   => 'Basic access for all registered users — no purchase required. Valid forever.',
                'plan_type'     => 'free',
                'price_bdt'     => 0,
                'duration_qty'  => 1,
                'duration_unit' => 'year',
                'features'      => self::freeFeatures(),
                'is_active'     => true,   // must be active so it can be auto-assigned and shown in plans list
                'sort_order'    => 0,
            ],

            // ─────────────────────────────────────────────────────────────
            // SILVER
            // ─────────────────────────────────────────────────────────────
            [
                'name'          => 'Silver — 1 Month',
                'slug'          => 'silver-1month',
                'description'   => 'Unlock chat and basic contact views for 1 month.',
                'plan_type'     => 'silver',
                'price_bdt'     => 500,
                'duration_qty'  => 1,
                'duration_unit' => 'month',
                'features'      => self::silverFeatures(),
                'is_active'     => true,
                'sort_order'    => 10,
            ],
            [
                'name'          => 'Silver — 3 Months',
                'slug'          => 'silver-3month',
                'description'   => 'Save ~10% — Silver access for 3 months.',
                'plan_type'     => 'silver',
                'price_bdt'     => 1_350,
                'duration_qty'  => 3,
                'duration_unit' => 'month',
                'features'      => self::silverFeatures(),
                'is_active'     => true,
                'sort_order'    => 11,
            ],
            [
                'name'          => 'Silver — 6 Months',
                'slug'          => 'silver-6month',
                'description'   => 'Save ~15% — Silver access for 6 months.',
                'plan_type'     => 'silver',
                'price_bdt'     => 2_550,
                'duration_qty'  => 6,
                'duration_unit' => 'month',
                'features'      => self::silverFeatures(),
                'is_active'     => true,
                'sort_order'    => 12,
            ],
            [
                'name'          => 'Silver — 1 Year',
                'slug'          => 'silver-1year',
                'description'   => 'Save ~20% — Silver access for a full year.',
                'plan_type'     => 'silver',
                'price_bdt'     => 4_800,
                'duration_qty'  => 1,
                'duration_unit' => 'year',
                'features'      => self::silverFeatures(),
                'is_active'     => true,
                'sort_order'    => 13,
            ],

            // ─────────────────────────────────────────────────────────────
            // GOLD
            // ─────────────────────────────────────────────────────────────
            [
                'name'          => 'Gold — 1 Month',
                'slug'          => 'gold-1month',
                'description'   => 'Full audio & video calls, who-liked-me and more for 1 month.',
                'plan_type'     => 'gold',
                'price_bdt'     => 1_200,
                'duration_qty'  => 1,
                'duration_unit' => 'month',
                'features'      => self::goldFeatures(),
                'is_active'     => true,
                'sort_order'    => 20,
            ],
            [
                'name'          => 'Gold — 3 Months',
                'slug'          => 'gold-3month',
                'description'   => 'Save ~10% — Gold access for 3 months.',
                'plan_type'     => 'gold',
                'price_bdt'     => 3_240,
                'duration_qty'  => 3,
                'duration_unit' => 'month',
                'features'      => self::goldFeatures(),
                'is_active'     => true,
                'sort_order'    => 21,
            ],
            [
                'name'          => 'Gold — 6 Months',
                'slug'          => 'gold-6month',
                'description'   => 'Save ~15% — Gold access for 6 months.',
                'plan_type'     => 'gold',
                'price_bdt'     => 6_120,
                'duration_qty'  => 6,
                'duration_unit' => 'month',
                'features'      => self::goldFeatures(),
                'is_active'     => true,
                'sort_order'    => 22,
            ],
            [
                'name'          => 'Gold — 1 Year',
                'slug'          => 'gold-1year',
                'description'   => 'Save ~20% — Gold access for a full year.',
                'plan_type'     => 'gold',
                'price_bdt'     => 11_520,
                'duration_qty'  => 1,
                'duration_unit' => 'year',
                'features'      => self::goldFeatures(),
                'is_active'     => true,
                'sort_order'    => 23,
            ],

            // ─────────────────────────────────────────────────────────────
            // PLATINUM
            // ─────────────────────────────────────────────────────────────
            [
                'name'          => 'Platinum — 1 Month',
                'slug'          => 'platinum-1month',
                'description'   => 'Maximum visibility and every feature unlocked for 1 month.',
                'plan_type'     => 'platinum',
                'price_bdt'     => 2_500,
                'duration_qty'  => 1,
                'duration_unit' => 'month',
                'features'      => self::platinumFeatures(),
                'is_active'     => true,
                'sort_order'    => 30,
            ],
            [
                'name'          => 'Platinum — 3 Months',
                'slug'          => 'platinum-3month',
                'description'   => 'Save ~10% — Platinum access for 3 months.',
                'plan_type'     => 'platinum',
                'price_bdt'     => 6_750,
                'duration_qty'  => 3,
                'duration_unit' => 'month',
                'features'      => self::platinumFeatures(),
                'is_active'     => true,
                'sort_order'    => 31,
            ],
            [
                'name'          => 'Platinum — 6 Months',
                'slug'          => 'platinum-6month',
                'description'   => 'Save ~15% — Platinum access for 6 months.',
                'plan_type'     => 'platinum',
                'price_bdt'     => 12_750,
                'duration_qty'  => 6,
                'duration_unit' => 'month',
                'features'      => self::platinumFeatures(),
                'is_active'     => true,
                'sort_order'    => 32,
            ],
            [
                'name'          => 'Platinum — 1 Year',
                'slug'          => 'platinum-1year',
                'description'   => 'Save ~20% — Platinum access for a full year.',
                'plan_type'     => 'platinum',
                'price_bdt'     => 24_000,
                'duration_qty'  => 1,
                'duration_unit' => 'year',
                'features'      => self::platinumFeatures(),
                'is_active'     => true,
                'sort_order'    => 33,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command->info('✅ Subscription plans seeded: ' . count($plans) . ' plans (Free + Silver/Gold/Platinum × 4 durations).');
    }
}

