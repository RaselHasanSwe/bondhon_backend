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
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // ── Site Settings & Pages ──
        $this->call(SiteSettingSeeder::class);
        $this->call(PageSeeder::class);

        $this->call(SubscriptionTypeSeeder::class);
        $this->call(SubscriptionPlanSeeder::class);

        // Select options must exist before user/profile factories run
        $this->call(SelectOptionSeeder::class);
        $this->call(OptionGroupConfigSeeder::class);

        $this->call(UserSeeder::class);
        $this->call(UserSubscriptionSeeder::class);

        // Create an admin user (idempotent)
        User::firstOrCreate(
            ['email' => 'admin@Enorsia.com'],
            [
                'name'               => 'Admin User',
                'email'              => 'admin@Enorsia.com',
                'password'           => Hash::make('123456789'),
                'gender'             => 'male',
                'profile_created_by' => 'self',
                'role'               => 'admin',
                'is_active'          => true,
                'is_banned'          => false,
                'subscription_plan'  => 'free',
                'email_verified_at'  => now(),
            ]
        );
    }
}

