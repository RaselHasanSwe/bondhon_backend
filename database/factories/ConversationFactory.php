<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        $userOne = User::inRandomOrder()->first() ?? User::factory()->create();
        $userTwo = User::inRandomOrder()->where('id', '!=', $userOne->id)->first() ?? User::factory()->create();

        // Ensure user_one_id is always less than user_two_id
        if ($userOne->id > $userTwo->id) {
            [$userOne, $userTwo] = [$userTwo, $userOne];
        }

        return [
            'user_one_id' => $userOne->id,
            'user_two_id' => $userTwo->id,
            'last_message_at' => fake()->boolean(70) ? now()->subHours(fake()->numberBetween(1, 168)) : null,
        ];
    }
}
