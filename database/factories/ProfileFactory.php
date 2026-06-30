<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use App\Models\SelectOption;
use App\Services\ProfileService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    private function getRandomOption(string $groupKey, ?int $parentId = null): ?string
    {
        $query = SelectOption::where('group_key', $groupKey)
            ->where('is_active', true);

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        return $query->inRandomOrder()->first()?->value;
    }

    private function getOptions(string $groupKey, ?int $parentId = null): array
    {
        $query = SelectOption::where('group_key', $groupKey)
            ->where('is_active', true);

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        return $query->pluck('value')->toArray();
    }

    private function getParentId(string $groupKey, ?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return SelectOption::where('group_key', $groupKey)
            ->where('value', $value)
            ->where('is_active', true)
            ->first()?->id;
    }

    private function pickRandomFrom(array $options, int $count = 1): array
    {
        if (empty($options)) {
            return [];
        }

        $count = min($count, count($options));

        return fake()->randomElements($options, $count);
    }

    /**
     * Bangladesh location: division → city, district → state (matches profile edit UI).
     *
     * @return array{country: string, city: string|null, state: string|null}
     */
    private function pickBangladeshLocation(): array
    {
        $bangladeshId = $this->getParentId('country', 'bangladesh');
        $division = $this->getRandomOption('country', $bangladeshId);
        $district = null;

        if ($division) {
            $divisionId = $this->getParentId('country', $division);
            $district = $this->getRandomOption('country', $divisionId);
        }

        return [
            'country' => 'bangladesh',
            'city' => $division,
            'state' => $district,
        ];
    }

    /**
     * Build profile attributes aligned with UpdateProfileRequest and the edit-profile form.
     */
    private function makeProfileAttributes(User $user): array
    {
        $gender = $user->gender ?? fake()->randomElement(['male', 'female']);
        $age = fake()->numberBetween(22, 45);
        $dob = now()->subYears($age)->subDays(fake()->numberBetween(1, 365));

        $location = $this->pickBangladeshLocation();

        $heightCm = $gender === 'male'
            ? fake()->numberBetween(165, 185)
            : fake()->numberBetween(150, 170);

        $weightKg = $gender === 'male'
            ? fake()->numberBetween(60, 85)
            : fake()->numberBetween(50, 70);

        return [
            'nick_name' => fake()->firstName(),
            'profile_created_for' => $this->getRandomOption('profile_created_for') ?? 'self',
            'looking_for' => $gender === 'male' ? 'bride' : 'groom',
            'dob' => $dob,
            'height_cm' => $heightCm,
            'weight_kg' => $weightKg,
            'body_type' => $this->getRandomOption('body_type'),
            'eye_color' => $this->getRandomOption('eye_color'),
            'hair_color' => $this->getRandomOption('hair_color'),
            'complexion' => $this->getRandomOption('complexion'),
            'blood_group' => $this->getRandomOption('blood_group'),
            'marital_status' => $this->getRandomOption('marital_status'),
            'disability' => $this->getRandomOption('disability') ?? 'none',
            'mother_tongue' => $this->getRandomOption('mother_tongue') ?? 'bengali',
            'nationality' => $this->getRandomOption('nationality') ?? 'bangladeshi',
            'country' => $location['country'],
            'city' => $location['city'],
            'state' => $location['state'],
            'postal_code' => (string) fake()->numberBetween(1000, 9999),
            'residing_status' => $this->getRandomOption('residing_status') ?? 'citizen',
            'about_me' => fake()->paragraphs(2, true),
            'what_looking_for' => fake()->paragraph(2),
            'profile_completion_percentage' => 0,
            'is_verified' => fake()->boolean(20),
            'is_photo_approved' => false,
            'last_seen_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'privacy_settings' => [
                'show_photo_to' => fake()->randomElement(['all', 'connections_only', 'none']),
                'show_phone_to' => 'connections_only',
                'show_email_to' => 'none',
                'hide_profile_from' => [],
                'show_online_status' => fake()->boolean(80),
            ],
            'custom_fields' => null,
        ];
    }

    public function definition(): array
    {
        return array_merge(
            ['user_id' => User::factory()],
            $this->makeProfileAttributes(
                new User(['gender' => fake()->randomElement(['male', 'female'])])
            ),
            ['profile_id' => 'BON-' . fake()->unique()->numberBetween(100000, 999999)]
        );
    }

    /**
     * Populate (or update) the profile for an existing user without creating a duplicate row.
     */
    public function seedForUser(User $user): Profile
    {
        if (! $user->profile) {
            app(ProfileService::class)->createProfile($user->id);
            $user->refresh();
        }

        $user->profile->update($this->makeProfileAttributes($user));

        return $user->profile->fresh();
    }

    public function withReligiousDetails(): static
    {
        return $this->afterCreating(function (Profile $profile) {
            $this->seedReligiousDetails($profile->user);
        });
    }

    private function seedReligiousDetails(User $user): void
    {
        $religion = $this->getRandomOption('religion') ?? 'islam';
        $religionParentId = $this->getParentId('religion', $religion);
        $caste = $this->getRandomOption('caste', $religionParentId);

        $user->religiousDetail()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'religion' => $religion,
                'caste' => $caste,
                'sub_caste' => null,
                'gotra' => null,
                'manglik_status' => $this->getRandomOption('manglik_status'),
                'religiousness' => $this->getRandomOption('religiousness'),
                'pray' => $this->getRandomOption('pray'),
            ]
        );
    }

    public function withFamilyDetails(): static
    {
        return $this->afterCreating(function (Profile $profile) {
            $this->seedFamilyDetails($profile->user);
        });
    }

    private function seedFamilyDetails(User $user): void
    {
        $hasChildren = $this->getRandomOption('have_children') ?? 'no';

        $user->familyDetail()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'family_type' => $this->getRandomOption('family_type'),
                'family_status' => $this->getRandomOption('family_status'),
                'family_income_bdt_per_month' => fake()->numberBetween(50000, 500000),
                'father_occupation' => $this->getRandomOption('occupation'),
                'mother_occupation' => $this->getRandomOption('occupation'),
                'brothers_count' => fake()->numberBetween(0, 4),
                'sisters_count' => fake()->numberBetween(0, 4),
                'has_children' => $hasChildren,
                'child_living_status' => $hasChildren === 'yes'
                    ? fake()->randomElement(['child_living_with_me', 'child_not_living_with_me'])
                    : 'no_child',
                'family_values' => $this->getRandomOption('family_values'),
                'sibling_position' => fake()->numberBetween(1, 5),
            ]
        );
    }

    public function withEducationCareer(): static
    {
        return $this->afterCreating(function (Profile $profile) {
            $this->seedEducationCareer($profile->user);
        });
    }

    private function seedEducationCareer(User $user): void
    {
        $user->educationCareer()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'highest_education' => $this->getRandomOption('education_level'),
                'college_university' => fake()->randomElement([
                    'Dhaka University', 'BUET', 'North South University', 'BRAC University',
                    'Chittagong University', 'Rajshahi University', 'Jahangirnagar University',
                ]),
                'institution_name_year' => null,
                'employer_name' => fake()->company(),
                'job_location' => fake()->randomElement(['Dhaka', 'Chittagong', 'Khulna', 'Rajshahi', 'Sylhet']),
                'designation' => fake()->randomElement([
                    'Software Engineer', 'Manager', 'Executive', 'Consultant',
                    'Analyst', 'Teacher', 'Senior Developer', 'Team Lead',
                ]),
                'experience_years' => fake()->numberBetween(1, 20),
                'profession' => $this->getRandomOption('profession'),
                'employed_in' => $this->getRandomOption('employed_in'),
                'annual_income_bdt' => fake()->numberBetween(300000, 3000000),
            ]
        );
    }

    public function withLifestyle(): static
    {
        return $this->afterCreating(function (Profile $profile) {
            $this->seedLifestyle($profile->user);
        });
    }

    private function seedLifestyle(User $user): void
    {
        $hobbyOptions = $this->getOptions('hobbies');
        $hobbies = $this->pickRandomFrom($hobbyOptions, fake()->numberBetween(2, 5));

        $languageOptions = $this->getOptions('mother_tongue');
        $languages = $this->pickRandomFrom(
            ! empty($languageOptions) ? $languageOptions : ['bengali', 'english'],
            fake()->numberBetween(1, 2)
        );

        $user->lifestyle()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'diet' => $this->getRandomOption('diet'),
                'smoking' => $this->getRandomOption('smoking'),
                'drinking' => $this->getRandomOption('drinking'),
                'eye_wear' => $this->getRandomOption('eye_wear'),
                'hobbies' => $hobbies,
                'languages_known' => $languages,
            ]
        );
    }

    public function withHoroscope(): static
    {
        return $this->afterCreating(function (Profile $profile) {
            $this->seedHoroscope($profile->user);
        });
    }

    private function seedHoroscope(User $user): void
    {
        $user->horoscopeDetail()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'birth_place' => fake()->randomElement([
                    'Dhaka', 'Chittagong', 'Sylhet', 'Khulna', 'Rajshahi', 'Barisal', 'Rangpur', 'Mymensingh',
                ]),
                'birth_time' => fake()->time('H:i'),
                'rashi' => $this->getRandomOption('rashi'),
                'nakshatra' => null,
                'manglik' => fake()->boolean(30),
            ]
        );
    }

    public function withPartnerPreferences(): static
    {
        return $this->afterCreating(function (Profile $profile) {
            $this->seedPartnerPreferences($profile->user);
        });
    }

    private function seedPartnerPreferences(User $user): void
    {
        $gender = $user->gender;
        $religion = $user->religiousDetail?->religion ?? $this->getRandomOption('religion') ?? 'islam';
        $religionParentId = $this->getParentId('religion', $religion);
        $casteOptions = $this->getOptions('caste', $religionParentId);
        $caste = $this->pickRandomFrom($casteOptions, 1);

        $bodyTypeOptions = $this->getOptions('body_type');
        $complexionOptions = $this->getOptions('complexion');
        $bloodGroupOptions = $this->getOptions('blood_group');
        $motherTongueOptions = $this->getOptions('mother_tongue');
        $manglikOptions = $this->getOptions('manglik_status');
        $rashiOptions = $this->getOptions('rashi');
        $religiousnessOptions = $this->getOptions('religiousness');
        $prayOptions = $this->getOptions('pray');
        $maritalOptions = $this->getOptions('marital_status');
        $dietOptions = $this->getOptions('diet');
        $educationOptions = $this->getOptions('education_level');
        $professionOptions = $this->getOptions('profession');
        $familyTypeOptions = $this->getOptions('family_type');
        $familyValuesOptions = $this->getOptions('family_values');
        $workingStatusOptions = $this->getOptions('working_status');
        $employedInOptions = $this->getOptions('employed_in');
        $residingStatusOptions = $this->getOptions('residing_status');
        $childLivingOptions = $this->getOptions('child_living_status');

        $bangladeshId = $this->getParentId('country', 'bangladesh');
        $division = $this->getRandomOption('country', $bangladeshId);
        $divisionId = $division ? $this->getParentId('country', $division) : null;
        $district = $divisionId ? $this->getRandomOption('country', $divisionId) : null;

        $user->partnerPreference()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'age_min' => fake()->numberBetween(22, 30),
                'age_max' => fake()->numberBetween(30, 45),
                'height_min_cm' => $gender === 'male' ? fake()->numberBetween(150, 160) : fake()->numberBetween(155, 165),
                'height_max_cm' => $gender === 'male' ? fake()->numberBetween(170, 185) : fake()->numberBetween(165, 180),
                'marital_status' => $this->pickRandomFrom($maritalOptions, fake()->numberBetween(1, 2)),
                'religion' => [$religion],
                'caste' => $caste,
                'education' => $this->pickRandomFrom($educationOptions, fake()->numberBetween(1, 3)),
                'profession' => $this->pickRandomFrom($professionOptions, fake()->numberBetween(1, 4)),
                'income_min_bdt' => fake()->numberBetween(300000, 500000),
                'income_max_bdt' => fake()->numberBetween(500000, 1000000),
                'country' => ['bangladesh'],
                'pref_divisions' => $division ? [$division] : [],
                'pref_districts' => $district ? [$district] : [],
                'pref_provinces' => null,
                'pref_states' => null,
                'diet' => $this->pickRandomFrom($dietOptions, fake()->numberBetween(1, 2)),
                'smoking_acceptable' => fake()->boolean(),
                'drinking_acceptable' => fake()->boolean(),
                'body_type' => $this->pickRandomFrom($bodyTypeOptions, fake()->numberBetween(1, 3)),
                'complexion' => $this->pickRandomFrom($complexionOptions, fake()->numberBetween(1, 3)),
                'blood_group' => $this->pickRandomFrom($bloodGroupOptions, fake()->numberBetween(1, 3)),
                'mother_tongue' => $this->pickRandomFrom($motherTongueOptions, fake()->numberBetween(1, 2)),
                'manglik_status' => $this->pickRandomFrom($manglikOptions, fake()->numberBetween(1, 2)),
                'rashi' => $this->pickRandomFrom($rashiOptions, fake()->numberBetween(1, 3)),
                'religiousness' => $this->pickRandomFrom($religiousnessOptions, fake()->numberBetween(1, 2)),
                'pray' => $this->pickRandomFrom($prayOptions, fake()->numberBetween(1, 2)),
                'has_children' => $this->getRandomOption('pref_has_children') ?? 'any',
                'child_living_status' => $this->pickRandomFrom($childLivingOptions, fake()->numberBetween(1, 2)),
                'family_type' => $this->pickRandomFrom($familyTypeOptions, fake()->numberBetween(1, 2)),
                'family_values' => $this->pickRandomFrom($familyValuesOptions, fake()->numberBetween(1, 2)),
                'working_status' => $this->pickRandomFrom($workingStatusOptions, fake()->numberBetween(1, 2)),
                'employed_in' => $this->pickRandomFrom($employedInOptions, fake()->numberBetween(1, 2)),
                'pref_residing_status' => $this->pickRandomFrom($residingStatusOptions, fake()->numberBetween(1, 2)),
            ]
        );
    }

    /**
     * Seed a full profile for an existing user (used by UserSeeder).
     */
    public function completeForUser(User $user): Profile
    {
        $profile = $this->seedForUser($user);

        $this->seedReligiousDetails($user);
        $this->seedFamilyDetails($user);
        $this->seedEducationCareer($user);
        $this->seedLifestyle($user);
        $this->seedHoroscope($user);
        $this->seedPartnerPreferences($user);

        return $profile->fresh();
    }

    /**
     * Complete profile with all sections filled (no photos).
     */
    public function complete(): static
    {
        return $this->withReligiousDetails()
            ->withFamilyDetails()
            ->withEducationCareer()
            ->withLifestyle()
            ->withHoroscope()
            ->withPartnerPreferences();
    }
}
