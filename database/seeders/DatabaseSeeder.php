<?php

namespace Database\Seeders;

use App\Models\CallLog;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\Message;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Subscription Plans (must run before subscriptions are created) ──
        $this->call(SubscriptionPlanSeeder::class);

        // Create an admin user (idempotent)
        $admin = User::firstOrCreate(
            ['email' => 'admin@bondhon.com'],
            [
                'name'               => 'Admin User',
                'email'              => 'admin@bondhon.com',
                'password'           => bcrypt('password'),
                'gender'             => 'male',
                'profile_created_by' => 'self',
                'role'               => 'admin',
                'is_active'          => true,
                'is_banned'          => false,
                'subscription_plan'  => 'free',
                'email_verified_at'  => now(),
            ]
        );

        // Create admin profile (idempotent)
        if (! $admin->profile) {
            Profile::factory()->verified()->create([
                'user_id'    => $admin->id,
                'profile_id' => 'BON-000001',
            ]);
        }

        // Create test user (idempotent)
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'               => 'Test User',
                'email'              => 'test@example.com',
                'password'           => bcrypt('password'),
                'gender'             => 'female',
                'profile_created_by' => 'self',
                'role'               => 'user',
                'is_active'          => true,
                'is_banned'          => false,
                'subscription_plan'  => 'free',
                'email_verified_at'  => now(),
            ]
        );

        // Create test user profile (idempotent)
        if (! $testUser->profile) {
            Profile::factory()->verified()->create([
                'user_id'    => $testUser->id,
                'profile_id' => 'BON-000002',
            ]);
        }

        // Create photos for test user (only if none exist)
        if ($testUser->photos()->count() === 0) {
            ProfilePhoto::factory(3)->create([
                'user_id' => $testUser->id,
            ]);

            // Create one primary photo
            ProfilePhoto::factory()->primary()->approved()->create([
                'user_id' => $testUser->id,
            ]);
        }

        // Create some other users with profiles
        $users = User::factory(8)->create();

        foreach ($users as $user) {
            Profile::factory()->create(['user_id' => $user->id]);
            ProfilePhoto::factory(2)->approved()->create(['user_id' => $user->id]);
        }

        // Create specific interests to avoid unique constraint violations
        $allUsers = collect([$admin, $testUser])->merge($users);
        $userArray = $allUsers->toArray();

        for ($i = 0; $i < min(10, count($userArray) - 1); $i++) {
            try {
                Interest::create([
                    'sender_id' => $userArray[$i]->id,
                    'receiver_id' => $userArray[$i + 1]->id,
                    'status' => fake()->randomElement(['pending', 'accepted', 'declined']),
                    'expires_at' => now()->addDays(30),
                ]);
            } catch (\Exception $e) {
                // Ignore duplicate key errors
            }
        }

        // Create some conversations
        Conversation::factory(3)->create();

        // Create some messages in those conversations
        Message::factory(20)->create();

        // Create some call logs
        CallLog::factory(5)->create();

        // Create some active subscriptions
        $userIds = $users->pluck('id')->toArray();
        foreach (array_slice($userIds, 0, 3) as $userId) {
            Subscription::factory()->active()->create([
                'user_id' => $userId,
            ]);
        }

        // Create some expired subscriptions
        foreach (array_slice($userIds, 3, 2) as $userId) {
            Subscription::factory()->expired()->create([
                'user_id' => $userId,
            ]);
        }
    }
}

