<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $plan = fake()->randomElement(['silver', 'gold', 'platinum']);
        $prices = [
            'silver' => 299,
            'gold' => 699,
            'platinum' => 1299,
        ];

        return [
            'user_id' => User::factory(),
            'plan' => $plan,
            'amount_bdt' => $prices[$plan],
            'payment_method' => fake()->randomElement(['sslcommerz', 'bkash', 'nagad']),
            'transaction_id' => fake()->uuid(),
            'status' => fake()->randomElement(['pending', 'active', 'expired', 'refunded']),
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(25),
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'starts_at' => now()->subDays(35),
            'expires_at' => now()->subDays(5),
        ]);
    }
}
