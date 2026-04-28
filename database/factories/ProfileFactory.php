<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'profile_id' => 'BON-' . Str::padLeft(fake()->numberBetween(1, 999999), 6, '0'),
            'dob' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'height_cm' => fake()->numberBetween(150, 200),
            'weight_kg' => fake()->numberBetween(45, 100),
            'complexion' => fake()->randomElement(['very_fair', 'fair', 'wheatish', 'dark']),
            'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'marital_status' => fake()->randomElement(['never_married', 'divorced', 'widowed', 'awaiting_divorce']),
            'mother_tongue' => fake()->randomElement(['Bengali', 'Urdu', 'Hindi', 'English']),
            'nationality' => 'Bangladeshi',
            'country' => 'Bangladesh',
            'state' => fake()->randomElement(['Dhaka', 'Chittagong', 'Sylhet', 'Khulna']),
            'city' => fake()->city(),
            'about_me' => fake()->sentence(20),
            'profile_completion_percentage' => fake()->numberBetween(0, 100),
            'is_verified' => fake()->boolean(30),
            'is_photo_approved' => fake()->boolean(30),
            'last_seen_at' => now()->subHours(fake()->numberBetween(1, 168)),
            'privacy_settings' => json_encode([
                'show_photo_to' => 'all',
                'show_phone_to' => 'connections_only',
                'show_email_to' => 'none',
                'hide_profile_from' => [],
                'show_online_status' => true,
            ]),
        ];
    }

    /**
     * Indicate that the profile is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'is_photo_approved' => true,
            'profile_completion_percentage' => 100,
        ]);
    }

    /**
     * Indicate that the profile is incomplete.
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_completion_percentage' => fake()->numberBetween(10, 50),
        ]);
    }
}
