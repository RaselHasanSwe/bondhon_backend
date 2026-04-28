<?php

namespace Database\Factories;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interest>
 */
class InterestFactory extends Factory
{
    public function definition(): array
    {
        $sender = User::inRandomOrder()->first() ?? User::factory()->create();
        $receiver = User::inRandomOrder()->where('id', '!=', $sender->id)->first() ?? User::factory()->create();

        return [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => fake()->randomElement(['pending', 'accepted', 'declined', 'ignored', 'expired']),
            'expires_at' => now()->addDays(30),
        ];
    }

    /**
     * Indicate that the interest is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the interest is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'expires_at' => null,
        ]);
    }
}
