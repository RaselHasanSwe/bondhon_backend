<?php

use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ─── Own Profile (GET /api/v1/profile) ───────────────────────────────────────

test('authenticated user can view own profile', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/profile');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success', 'data' => ['id', 'name', 'gender', 'profile'],
             ]);
});

test('unauthenticated user cannot view profile', function () {
    $response = $this->getJson('/api/v1/profile');
    $response->assertStatus(401);
});

// ─── Update Profile (PUT /api/v1/profile) ────────────────────────────────────

test('user can update own profile basic info', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/v1/profile', [
        'dob'            => '1990-05-15',
        'height_cm'      => 175,
        'weight_kg'      => 70,
        'complexion'     => 'fair',
        'marital_status' => 'never_married',
        'mother_tongue'  => 'Bengali',
        'nationality'    => 'Bangladeshi',
        'country'        => 'Bangladesh',
        'city'           => 'Dhaka',
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'height_cm' => 175, 'city' => 'Dhaka']);
});

test('user can update religious details', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/v1/profile', [
        'religion'       => 'Islam',
        'manglik_status' => 'no',
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('religious_details', ['user_id' => $user->id, 'religion' => 'Islam']);
});

test('user can update family details', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/v1/profile', [
        'family_type'   => 'nuclear',
        'family_status' => 'middle_class',
        'brothers_count' => 2,
        'sisters_count'  => 1,
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('family_details', ['user_id' => $user->id, 'family_type' => 'nuclear']);
});

test('user can update education and career', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/v1/profile', [
        'highest_education' => 'Bachelor\'s Degree',
        'profession'        => 'Software Engineer',
        'employed_in'       => 'private',
        'annual_income_bdt' => 600000,
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('education_careers', ['user_id' => $user->id, 'profession' => 'Software Engineer']);
});

test('user can update lifestyle', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/v1/profile', [
        'diet'    => 'non_vegetarian',
        'smoking' => 'non_smoker',
        'drinking' => 'non_drinker',
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('lifestyles', ['user_id' => $user->id, 'diet' => 'non_vegetarian']);
});

test('profile update recalculates completion percentage', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create(['user_id' => $user->id, 'profile_completion_percentage' => 0]);

    $this->actingAs($user)->putJson('/api/v1/profile', [
        'dob'            => '1992-03-10',
        'height_cm'      => 168,
        'marital_status' => 'never_married',
        'mother_tongue'  => 'Bengali',
        'country'        => 'Bangladesh',
        'city'           => 'Chittagong',
    ]);

    $profile->refresh();
    expect($profile->profile_completion_percentage)->toBeGreaterThan(0);
});

test('profile update fails with invalid complexion', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/v1/profile', [
        'complexion' => 'invalid_value',
    ]);

    $response->assertStatus(422);
});

// ─── Partner Preferences (PUT /api/v1/preferences) ───────────────────────────

test('user can update partner preferences', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/v1/preferences', [
        'age_min'        => 25,
        'age_max'        => 35,
        'height_min_cm'  => 160,
        'height_max_cm'  => 185,
        'marital_status' => ['never_married'],
        'religion'       => ['Islam'],
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('partner_preferences', ['user_id' => $user->id, 'age_min' => 25, 'age_max' => 35]);
});

// ─── Profile Completion Status ────────────────────────────────────────────────

test('user can get profile completion status', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/profile/completion');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [
                     'percentage', 'has_basic_info', 'has_religious_detail',
                     'has_family_detail', 'has_education', 'has_lifestyle',
                     'has_horoscope', 'has_preferences', 'has_photo', 'has_about_me',
                 ],
             ]);
});

// ─── View Another Profile (GET /api/v1/profile/{profileId}) ──────────────────

test('user can view another user profile by profile id', function () {
    $viewer = User::factory()->create();
    Profile::factory()->create(['user_id' => $viewer->id]);

    $other = User::factory()->create();
    Profile::factory()->create(['user_id' => $other->id, 'profile_id' => 'BON-999999']);

    $response = $this->actingAs($viewer)->getJson('/api/v1/profile/BON-999999');

    $response->assertStatus(200)->assertJson(['success' => true]);
});

test('view profile records profile view', function () {
    $viewer = User::factory()->create();
    Profile::factory()->create(['user_id' => $viewer->id]);

    $other = User::factory()->create();
    Profile::factory()->create(['user_id' => $other->id, 'profile_id' => 'BON-888888']);

    $this->actingAs($viewer)->getJson('/api/v1/profile/BON-888888');

    $this->assertDatabaseHas('profile_views', [
        'viewer_id' => $viewer->id,
        'viewed_id' => $other->id,
    ]);
});

test('blocked user cannot view profile', function () {
    $viewer = User::factory()->create();
    Profile::factory()->create(['user_id' => $viewer->id]);

    $other = User::factory()->create();
    Profile::factory()->create(['user_id' => $other->id, 'profile_id' => 'BON-777777']);

    // viewer blocks other
    $viewer->blocks()->create(['blocked_id' => $other->id]);

    $response = $this->actingAs($viewer)->getJson('/api/v1/profile/BON-777777');

    $response->assertStatus(403);
});

test('viewing non-existent profile returns 404', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/profile/BON-000000');

    $response->assertStatus(404);
});

// ─── Photo Upload ─────────────────────────────────────────────────────────────

test('user can upload a profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $file = UploadedFile::fake()->image('avatar.jpg', 800, 600);

    $response = $this->actingAs($user)->postJson('/api/v1/profile/photos', [
        'photo'      => $file,
        'is_private' => false,
    ]);

    $response->assertStatus(201)->assertJson(['success' => true]);
    $this->assertDatabaseHas('profile_photos', ['user_id' => $user->id, 'moderation_status' => 'pending']);
});

test('photo upload fails without file', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/profile/photos', []);

    $response->assertStatus(422);
});

test('user can delete their own photo', function () {
    Storage::fake('public');

    $user  = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);
    $photo = ProfilePhoto::factory()->create(['user_id' => $user->id, 'file_path' => 'photos/test.jpg']);

    $response = $this->actingAs($user)->deleteJson('/api/v1/profile/photos/' . $photo->id);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseMissing('profile_photos', ['id' => $photo->id]);
});

test('user cannot delete another users photo', function () {
    $user  = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $other = User::factory()->create();
    Profile::factory()->create(['user_id' => $other->id]);
    $photo = ProfilePhoto::factory()->create(['user_id' => $other->id]);

    $response = $this->actingAs($user)->deleteJson('/api/v1/profile/photos/' . $photo->id);

    $response->assertStatus(404);
});

test('user can set approved photo as primary', function () {
    $user  = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);
    $photo = ProfilePhoto::factory()->approved()->create(['user_id' => $user->id, 'is_primary' => false]);

    $response = $this->actingAs($user)->putJson('/api/v1/profile/photos/' . $photo->id . '/primary');

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('profile_photos', ['id' => $photo->id, 'is_primary' => true]);
});

test('user cannot set unapproved photo as primary', function () {
    $user  = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);
    $photo = ProfilePhoto::factory()->create(['user_id' => $user->id, 'is_approved' => false]);

    $response = $this->actingAs($user)->putJson('/api/v1/profile/photos/' . $photo->id . '/primary');

    $response->assertStatus(403);
});

