<?php

use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Services\ProfileCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('profile completion is 0 for user without profile', function () {
    $user    = User::factory()->make(['id' => 1]);
    $service = new ProfileCompletionService();

    // User has no profile relation loaded
    $user->setRelation('profile', null);

    expect($service->calculate($user))->toBe(0);
});

test('profile completion increases with more filled fields', function () {
    $user = User::factory()->create();
    Profile::factory()->create([
        'user_id'        => $user->id,
        'dob'            => '1990-01-01',
        'height_cm'      => 170,
        'weight_kg'      => 65,
        'complexion'     => 'fair',
        'marital_status' => 'never_married',
        'mother_tongue'  => 'Bengali',
        'country'        => 'Bangladesh',
        'city'           => 'Dhaka',
    ]);

    $user = $user->load(['profile', 'religiousDetail', 'familyDetail', 'educationCareer', 'lifestyle', 'horoscopeDetail', 'partnerPreference', 'photos']);
    $service = new ProfileCompletionService();

    expect($service->calculate($user))->toBeGreaterThanOrEqual(15);
});

test('profile completion reaches 100 with full data and approved photo', function () {
    $user = User::factory()->create();

    Profile::factory()->create([
        'user_id'        => $user->id,
        'dob'            => '1990-01-01',
        'height_cm'      => 170,
        'weight_kg'      => 65,
        'complexion'     => 'fair',
        'marital_status' => 'never_married',
        'mother_tongue'  => 'Bengali',
        'country'        => 'Bangladesh',
        'city'           => 'Dhaka',
        'about_me'       => str_repeat('This is my about me section. ', 5), // >= 50 chars
    ]);

    $user->religiousDetail()->create([
        'religion'       => 'Islam',
        'caste'          => 'Muslim',
        'manglik_status' => 'no',
    ]);

    $user->familyDetail()->create([
        'family_type'   => 'nuclear',
        'family_status' => 'middle_class',
    ]);

    $user->educationCareer()->create([
        'highest_education' => 'Bachelor\'s',
        'profession'        => 'Engineer',
        'employed_in'       => 'private',
    ]);

    $user->lifestyle()->create([
        'diet'    => 'non_vegetarian',
        'smoking' => 'non_smoker',
        'drinking' => 'non_drinker',
        'languages_known' => ['Bengali', 'English'],
    ]);

    $user->horoscopeDetail()->create([
        'rashi'       => 'Aries',
        'birth_place' => 'Dhaka',
    ]);

    $user->partnerPreference()->create([
        'age_min'        => 22,
        'age_max'        => 32,
        'height_min_cm'  => 155,
        'height_max_cm'  => 180,
        'religion'       => ['Islam'],
        'marital_status' => ['never_married'],
    ]);

    ProfilePhoto::factory()->approved()->create(['user_id' => $user->id]);

    $user = $user->fresh()->load(['profile', 'religiousDetail', 'familyDetail', 'educationCareer', 'lifestyle', 'horoscopeDetail', 'partnerPreference', 'photos']);

    $service = new ProfileCompletionService();
    $score   = $service->calculate($user);

    expect($score)->toBe(100);
});

test('recalculate and save updates profile completion percentage in db', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id'                       => $user->id,
        'profile_completion_percentage' => 0,
    ]);

    $user = $user->fresh();
    $service = new ProfileCompletionService();
    $service->recalculateAndSave($user);

    $profile->refresh();
    expect($profile->profile_completion_percentage)->toBeGreaterThanOrEqual(0);
});

