<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        $conversation = Conversation::inRandomOrder()->first() ?? Conversation::factory()->create();
        $senderIsUserOne = fake()->boolean();
        $sender = $senderIsUserOne ? $conversation->user_one_id : $conversation->user_two_id;

        return [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender,
            'type' => fake()->randomElement(['text', 'image', 'document', 'voice']),
            'body' => fake()->sentence(),
            'file_path' => null,
            'is_deleted' => fake()->boolean(5),
            'delivered_at' => fake()->boolean(80) ? now()->subMinutes(fake()->numberBetween(1, 60)) : null,
            'read_at' => fake()->boolean(60) ? now()->subMinutes(fake()->numberBetween(1, 60)) : null,
        ];
    }

    /**
     * Indicate that the message is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }
}

