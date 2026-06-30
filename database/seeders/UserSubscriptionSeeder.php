<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSubscriptionSeeder extends Seeder
{
    protected int $goldPercentage = 70;

    public function run(): void
    {
        $this->command->info('🚀 Assigning subscriptions to users...');

        // Get subscription types in one query
        $types = SubscriptionType::whereIn('name', ['Free', 'Gold'])
            ->get()
            ->keyBy('name');

        $freeType = $types->get('Free');
        $goldType = $types->get('Gold');

        if (!$freeType || !$goldType) {
            $this->command->error('❌ Subscription types not found! Run SubscriptionTypeSeeder first.');
            return;
        }

        // Get subscription plans in one query
        $plans = SubscriptionPlan::whereIn('plan_type', [$freeType->id, $goldType->id])
            ->where('is_active', true)
            ->get()
            ->keyBy('plan_type');

        $freePlan = $plans->get($freeType->id);
        $goldPlan = $plans->get($goldType->id);

        if (!$freePlan) {
            $this->command->error('❌ No FREE subscription plan found! Run SubscriptionPlanSeeder first.');
            return;
        }

        if (!$goldPlan) {
            $this->command->error('❌ No GOLD subscription plan found! Run SubscriptionPlanSeeder first.');
            return;
        }

        $this->command->info("✅ Free plan: {$freePlan->name} (ID: {$freePlan->id})");
        $this->command->info("✅ Gold plan: {$goldPlan->name} (ID: {$goldPlan->id})");

        // Get all users
        $users = User::all();
        $totalUsers = $users->count();

        if ($totalUsers === 0) {
            $this->command->error('❌ No users found! Run UserSeeder first.');
            return;
        }

        $this->command->info("📊 Total users: {$totalUsers}");
        $this->command->info("📊 Gold: {$this->goldPercentage}% | Free: " . (100 - $this->goldPercentage) . "%");

        $goldCount = 0;
        $freeCount = 0;
        $subscriptions = [];

        $bar = $this->command->getOutput()->createProgressBar($totalUsers);

        foreach ($users as $user) {
            $isGold = fake()->boolean($this->goldPercentage);
            $plan = $isGold ? $goldPlan : $freePlan;

            // Calculate expiry date
            $expiresAt = null;
            if ($plan->duration_unit === 'month') {
                $expiresAt = now()->addMonths($plan->duration_qty);
            } elseif ($plan->duration_unit === 'year') {
                $expiresAt = now()->addYears($plan->duration_qty);
            } elseif ($plan->duration_unit === 'day') {
                $expiresAt = now()->addDays($plan->duration_qty);
            } else {
                $expiresAt = now()->addMonths(1);
            }

            // Generate transaction ID
            $transactionId = $isGold
                ? 'GOLD-' . strtoupper(Str::random(12))
                : 'FREE-' . strtoupper(Str::random(12));

            // Create subscription record
            $subscriptions[] = [
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'plan' => $isGold ? 'gold' : 'free',
                'amount_bdt' => $isGold ? $goldPlan->price_bdt : 0,
                'payment_method' => $isGold ? 'sslcommerz' : 'system',
                'transaction_id' => $transactionId,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Update user
            $user->update([
                'subscription_plan' => $isGold ? 'gold' : 'free',
                'subscription_expires_at' => $expiresAt,
                'active_subscription_id' => null,
            ]);

            if ($isGold) {
                $goldCount++;
            } else {
                $freeCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        // Bulk insert subscriptions
        DB::table('subscriptions')->insert($subscriptions);

        // Update users with active_subscription_id
        $this->command->info('🔄 Updating users with active_subscription_id...');

        foreach ($users as $user) {
            $subscription = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($subscription) {
                $user->update([
                    'active_subscription_id' => $subscription->id
                ]);
            }
        }

        $this->command->info("✅ Subscription assignment complete!");
        $this->command->info("   🥇 Gold: {$goldCount} users (paid, bypassing SSLCommerz)");
        $this->command->info("   🆓 Free: {$freeCount} users");
    }

    public function setGoldPercentage(int $percentage): self
    {
        $this->goldPercentage = $percentage;
        return $this;
    }
}