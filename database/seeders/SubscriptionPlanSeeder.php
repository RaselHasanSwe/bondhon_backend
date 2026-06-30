<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionType;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $types = SubscriptionType::whereIn('name', ['Free', 'Gold'])
            ->get()
            ->keyBy('name');

        $freeType = $types->get('Free');
        $goldType = $types->get('Gold');

        if (!$freeType || !$goldType) {            $this->command->error('❌ Subscription types not found! Run SubscriptionTypeSeeder first.');
            return;
        }

        // ── FREE Plan (1 month, view only) ──
        $freePlan = SubscriptionPlan::updateOrCreate(
            ['slug' => 'free-1month'],
            [
                'name' => 'Free — 1 Month',
                'description' => 'Basic viewing access. No premium features.',
                'plan_type' => $freeType->id,
                'price_bdt' => 0,
                'duration_qty' => 1,
                'duration_unit' => 'month',
                'features' => [
                    // Discovery & Search
                    'daily_matches' => 1,
                    'search_access' => true,
                    'profile_views_per_day' => 0,
                    'search_filters_advanced' => false,
                    'profile_id_search' => false,
                    // Communication
                    'send_interest_per_day' => 1,
                    'chat_access' => false,
                    'audio_call_access' => false,
                    'video_call_access' => false,
                    'message_read_receipt' => false,
                    // Visibility & Insights
                    'contact_info_views_per_month' => 0,
                    'see_who_liked_me' => false,
                    'see_who_viewed_profile' => false,
                    'profile_visitors_detailed' => false,
                    // Photos & Privacy
                    'max_photos_upload' => 1,
                    'profile_visibility_control' => false,
                    // Reports & Analytics
                    'compatibility_score_visible' => false,
                    'match_report_monthly' => false,
                    // Notifications
                    'push_notifications' => true,
                    'email_digest_frequency' => 'none',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // ── GOLD Plan (1 year, all access) ──
        $goldPlan = SubscriptionPlan::updateOrCreate(
            ['slug' => 'gold-1year'],
            [
                'name' => 'Gold — 1 Year',
                'description' => 'Full premium access with all features for 1 year.',
                'plan_type' => $goldType->id,
                'price_bdt' => 5000,
                'duration_qty' => 1,
                'duration_unit' => 'year',
                'features' => [
                    // Discovery & Search - Unlimited everything
                    'daily_matches' => -1, // unlimited
                    'search_access' => true,
                    'profile_views_per_day' => -1, // unlimited
                    'search_filters_advanced' => true,
                    'profile_id_search' => true,
                    // Communication - Full access
                    'send_interest_per_day' => -1, // unlimited
                    'chat_access' => true,
                    'audio_call_access' => true,
                    'video_call_access' => true,
                    'message_read_receipt' => true,
                    // Visibility & Insights - Complete visibility
                    'contact_info_views_per_month' => -1, // unlimited
                    'see_who_liked_me' => true,
                    'see_who_viewed_profile' => true,
                    'profile_visitors_detailed' => true,
                    // Photos & Privacy - Maximum
                    'max_photos_upload' => 20,
                    'profile_visibility_control' => true,
                    // Reports & Analytics - Full insights
                    'compatibility_score_visible' => true,
                    'match_report_monthly' => true,
                    // Notifications
                    'push_notifications' => true,
                    'email_digest_frequency' => 'daily',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $this->command->info('✅ Subscription plans seeded:');
        $this->command->info('   🆓 Free — 1 Month (view only)');
        $this->command->info('   🥇 Gold — 1 Year (all access, ৳5,000)');
    }
}