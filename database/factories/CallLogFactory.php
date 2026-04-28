<?php

namespace Database\Factories;

use App\Models\CallLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CallLog>
 */
class CallLogFactory extends Factory
{
    public function definition(): array
    {
        $caller = User::inRandomOrder()->first() ?? User::factory()->create();
        $receiver = User::inRandomOrder()->where('id', '!=', $caller->id)->first() ?? User::factory()->create();

        $startedAt = fake()->boolean(80) ? now()->subHours(fake()->numberBetween(1, 168)) : null;
        $endedAt = $startedAt && fake()->boolean(70) ? $startedAt->addMinutes(fake()->numberBetween(1, 60)) : null;

        return [
            'caller_id' => $caller->id,
            'receiver_id' => $receiver->id,
            'type' => fake()->randomElement(['audio', 'video']),
            'status' => fake()->randomElement(['initiated', 'answered', 'missed', 'declined', 'ended']),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $endedAt ? $endedAt->diffInSeconds($startedAt) : null,
        ];
    }
}
