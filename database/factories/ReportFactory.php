<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reported_id' => User::factory(),
            'reason'      => fake()->randomElement(['fake_profile', 'inappropriate_photo', 'abusive', 'spam', 'other']),
            'description' => fake()->optional()->sentence(),
            'status'      => 'pending',
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'reviewed']);
    }

    public function actionTaken(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'action_taken']);
    }

    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'dismissed']);
    }
}

