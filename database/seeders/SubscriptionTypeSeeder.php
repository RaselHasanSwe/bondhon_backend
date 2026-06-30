<?php

namespace Database\Seeders;

use App\Models\SubscriptionType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Free', 'sort_order' => 1],
            ['name' => 'Gold', 'sort_order' => 2],
        ];

        foreach ($types as $type) {
            SubscriptionType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }

        $this->command->info('✅ Subscription types seeded: Free, Gold');
    }
}
