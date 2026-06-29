<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Number of users to create
     * Change this to scale up/down
     */
    protected int $userCount = 1000;

    public function run(): void
    {
        $this->command->info('🚀 Creating users...');
        $this->command->info("📊 Target: {$this->userCount} users");

        // Create users
        User::factory($this->userCount)
            ->withCompleteProfile()
            ->create();

        $this->command->info("✅ Successfully created {$this->userCount} users with email verified!");
        $this->command->info("   All users have 'free' subscription plan (no active subscription)");
    }

    /**
     * Set the number of users to create
     */
    public function setUserCount(int $count): self
    {
        $this->userCount = $count;
        return $this;
    }
}