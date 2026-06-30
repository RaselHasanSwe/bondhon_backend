<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\SelectOption;
use App\Services\ProfileCompletionService;
use App\Services\ProfileService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);

        $profileCreatedBy = SelectOption::where('group_key', 'profile_created_by')
            ->where('is_active', true)
            ->inRandomOrder()
            ->first()?->value ?? 'self';

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'gender' => $gender,
            'email_verified_at' => now(),
            'profile_created_by' => $profileCreatedBy,
            'password' => static::$password ??= Hash::make('123456789'),
            'role' => 'user',
            'is_active' => true,
            'is_banned' => false,
            'subscription_plan' => 'free', // Default free
            'subscription_expires_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user should be an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the user should be banned.
     */
    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_banned' => true,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            app(ProfileService::class)->createProfile($user->id);
        });
    }

    public function withCompleteProfile(): static
    {
        return $this->afterCreating(function (User $user) {
            ProfileFactory::new()->completeForUser($user);

            app(ProfileCompletionService::class)->recalculateAndSave($user->fresh());
        });
    }

    public function withBasicProfile(): static
    {
        return $this->afterCreating(function (User $user) {
            ProfileFactory::new()->seedForUser($user);
        });
    }
}