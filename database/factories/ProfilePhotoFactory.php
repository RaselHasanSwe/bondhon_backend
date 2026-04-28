<?php

namespace Database\Factories;

use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfilePhoto>
 */
class ProfilePhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'file_path' => 'profiles/' . fake()->uuid() . '.jpg',
            'is_primary' => false,
            'is_approved' => fake()->boolean(50),
            'is_private' => false,
            'moderation_status' => fake()->randomElement(['pending', 'approved', 'rejected']),
        ];
    }

    /**
     * Indicate that the photo is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the photo is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
            'moderation_status' => 'approved',
        ]);
    }
}
