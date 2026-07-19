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
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'free-1month'],
            [
                'name' => 'Free — 1 Month',
                'description' => 'Basic viewing access. No premium features.',
                'plan_type' => $freeType->id,
                'price_bdt' => 0,
                'duration_qty' => 1,
                'duration_unit' => 'month',
                'features' => [
                    'daily_matches' => 1,
                    'profile_views_per_day' => 0,
                    'send_interest_per_day' => 1,
                    'chat_access' => false,
                    'audio_call_access' => false,
                    'video_call_access' => false,
                    'see_who_viewed_profile' => false,
                    'max_photos_upload' => 1,
                    'push_notifications' => true,
                    'email_digest_frequency' => 'none',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // ── GOLD Plan (1 year, all access) ──
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'gold-1year'],
            [
                'name' => 'Gold — 1 Year',
                'description' => 'Full premium access with all features for 1 year.',
                'plan_type' => $goldType->id,
                'price_bdt' => 5000,
                'duration_qty' => 1,
                'duration_unit' => 'year',
                'features' => [
                    'daily_matches' => -1,
                    'profile_views_per_day' => -1,
                    'send_interest_per_day' => -1,
                    'chat_access' => true,
                    'audio_call_access' => true,
                    'video_call_access' => true,
                    'see_who_viewed_profile' => true,
                    'max_photos_upload' => 20,
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
