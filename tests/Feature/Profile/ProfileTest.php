<?php

use App\Models\Interest;
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
    $viewer = User::factory()->create(['subscription_plan' => 'silver']);
    Profile::factory()->create(['user_id' => $viewer->id]);
    $subscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $viewer->id,
        'plan'       => 'silver',
        'amount_bdt' => 499,
    ]);
    $viewer->update([
        'active_subscription_id'  => $subscription->id,
        'subscription_expires_at' => $subscription->expires_at,
    ]);

    $other = User::factory()->create();
    Profile::factory()->create(['user_id' => $other->id, 'profile_id' => 'BON-999999']);

    $response = $this->actingAs($viewer)->getJson('/api/v1/profile/BON-999999');

    $response->assertStatus(200)->assertJson(['success' => true]);
});

test('free user cannot view another users profile', function () {
    $viewer = User::factory()->create(['subscription_plan' => 'free']);
    Profile::factory()->create(['user_id' => $viewer->id]);

    $other = User::factory()->create();
    Profile::factory()->create(['user_id' => $other->id, 'profile_id' => 'BON-999999']);

    $response = $this->actingAs($viewer)->getJson('/api/v1/profile/BON-999999');

    $response->assertStatus(403)
        ->assertJsonPath('errors.feature', 'full_profile_access');
});

test('paid user receives full profile access', function () {
    $viewer = User::factory()->create(['subscription_plan' => 'silver']);
    Profile::factory()->create(['user_id' => $viewer->id]);

    $subscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $viewer->id,
        'plan'       => 'silver',
        'amount_bdt' => 499,
    ]);
    $viewer->update([
        'active_subscription_id'  => $subscription->id,
        'subscription_expires_at' => $subscription->expires_at,
    ]);

    $other = User::factory()->create();
    Profile::factory()->create(['user_id' => $other->id, 'profile_id' => 'BON-888888']);

    $response = $this->actingAs($viewer)->getJson('/api/v1/profile/BON-888888');

    $response->assertStatus(200)
        ->assertJsonPath('data.access.full_profile', true)
        ->assertJsonStructure([
            'data' => [
                'access' => ['full_profile', 'profile_views_per_day' => ['limit', 'used', 'unlimited', 'remaining']],
            ],
        ]);
});

test('viewing another profile includes viewer context in one response', function () {
    $viewer = User::factory()->create(['subscription_plan' => 'silver']);
    Profile::factory()->create(['user_id' => $viewer->id]);

    $subscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $viewer->id,
        'plan'       => 'silver',
        'amount_bdt' => 499,
    ]);
    $viewer->update([
        'active_subscription_id'  => $subscription->id,
        'subscription_expires_at' => $subscription->expires_at,
    ]);

    $other = User::factory()->create();
    Profile::factory()->create(['user_id' => $other->id, 'profile_id' => 'BON-CTX001']);

    \App\Models\Shortlist::create([
        'user_id'             => $viewer->id,
        'shortlisted_user_id' => $other->id,
    ]);

    Interest::factory()->create([
        'sender_id'   => $viewer->id,
        'receiver_id' => $other->id,
        'status'      => 'pending',
    ]);

    $response = $this->actingAs($viewer)->getJson('/api/v1/profile/BON-CTX001');

    $response->assertStatus(200)
        ->assertJsonPath('data.connection_status', 'pending')
        ->assertJsonPath('data.is_interest_sender', true)
        ->assertJsonPath('data.can_send_interest', false)
        ->assertJsonPath('data.is_shortlisted', true)
        ->assertJsonStructure([
            'data' => [
                'connection_status',
                'interest_id',
                'is_interest_sender',
                'can_send_interest',
                'is_shortlisted',
                'compatibility_score' => ['score', 'score_breakdown', 'calculated_at'],
            ],
        ]);
});

test('viewing own profile does not include viewer context fields', function () {
    $user = User::factory()->create(['subscription_plan' => 'silver']);
    Profile::factory()->create(['user_id' => $user->id, 'profile_id' => 'BON-OWNCTX']);
    $subscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $user->id,
        'plan'       => 'silver',
        'amount_bdt' => 499,
    ]);
    $user->update([
        'active_subscription_id'  => $subscription->id,
        'subscription_expires_at' => $subscription->expires_at,
    ]);

    $response = $this->actingAs($user)->getJson('/api/v1/profile/BON-OWNCTX');

    $response->assertStatus(200)
        ->assertJsonMissingPath('data.connection_status')
        ->assertJsonMissingPath('data.is_shortlisted')
        ->assertJsonMissingPath('data.compatibility_score');
});

test('paid user cannot exceed daily profile view limit', function () {
    $viewer = User::factory()->create(['subscription_plan' => 'silver']);
    Profile::factory()->create(['user_id' => $viewer->id]);

    $subscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $viewer->id,
        'plan'       => 'silver',
        'amount_bdt' => 499,
    ]);
    $viewer->update([
        'active_subscription_id'  => $subscription->id,
        'subscription_expires_at' => $subscription->expires_at,
    ]);

    for ($i = 0; $i < 10; $i++) {
        $other = User::factory()->create();
        Profile::factory()->create(['user_id' => $other->id, 'profile_id' => 'BON-LIM' . str_pad((string) $i, 3, '0', STR_PAD_LEFT)]);
        \App\Models\ProfileView::create([
            'viewer_id' => $viewer->id,
            'viewed_id' => $other->id,
            'viewed_at' => now(),
        ]);
    }

    $blocked = User::factory()->create();
    Profile::factory()->create(['user_id' => $blocked->id, 'profile_id' => 'BON-LIMITED']);

    $response = $this->actingAs($viewer)->getJson('/api/v1/profile/BON-LIMITED');

    $response->assertStatus(403)
        ->assertJsonPath('errors.feature', 'profile_views_per_day');
});

test('view profile records profile view', function () {
    $viewer = User::factory()->create(['subscription_plan' => 'silver']);
    Profile::factory()->create(['user_id' => $viewer->id]);
    $subscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $viewer->id,
        'plan'       => 'silver',
        'amount_bdt' => 499,
    ]);
    $viewer->update([
        'active_subscription_id'  => $subscription->id,
        'subscription_expires_at' => $subscription->expires_at,
    ]);

    $other = User::factory()->create();
    Profile::factory()->create(['user_id' => $other->id, 'profile_id' => 'BON-888888']);

    $this->actingAs($viewer)->getJson('/api/v1/profile/BON-888888');

    $this->assertDatabaseHas('profile_views', [
        'viewer_id' => $viewer->id,
        'viewed_id' => $other->id,
    ]);
});

test('viewing own profile does not record a profile view', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $user = User::factory()->create(['subscription_plan' => 'silver']);
    Profile::factory()->create(['user_id' => $user->id, 'profile_id' => 'BON-SELFV']);
    $subscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $user->id,
        'plan'       => 'silver',
        'amount_bdt' => 499,
    ]);
    $user->update([
        'active_subscription_id'  => $subscription->id,
        'subscription_expires_at' => $subscription->expires_at,
    ]);

    $response = $this->actingAs($user)->getJson('/api/v1/profile/BON-SELFV');

    $response->assertStatus(200);

    $this->assertDatabaseMissing('profile_views', [
        'viewer_id' => $user->id,
        'viewed_id' => $user->id,
    ]);

    \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\SendProfileViewedEmail::class);
});

test('view profile notifies paid profile owner with in-app and email', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $viewer = User::factory()->create(['subscription_plan' => 'silver']);
    Profile::factory()->create(['user_id' => $viewer->id]);
    $viewerSubscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $viewer->id,
        'plan'       => 'silver',
        'amount_bdt' => 499,
    ]);
    $viewer->update([
        'active_subscription_id'  => $viewerSubscription->id,
        'subscription_expires_at' => $viewerSubscription->expires_at,
    ]);

    $viewed = User::factory()->create(['subscription_plan' => 'gold']);
    Profile::factory()->create(['user_id' => $viewed->id, 'profile_id' => 'BON-NOTIFY']);
    $viewedSubscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $viewed->id,
        'plan'       => 'gold',
        'amount_bdt' => 699,
    ]);
    $viewed->update([
        'active_subscription_id'  => $viewedSubscription->id,
        'subscription_expires_at' => $viewedSubscription->expires_at,
    ]);

    $this->actingAs($viewer)->getJson('/api/v1/profile/BON-NOTIFY');

    $this->assertDatabaseHas('notifications', [
        'notifiable_id'   => $viewed->id,
        'notifiable_type' => User::class,
        'type'            => 'profile_viewed',
    ]);

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendProfileViewedEmail::class, function ($job) use ($viewer, $viewed) {
        return $job->viewerId === $viewer->id && $job->viewedId === $viewed->id;
    });
});

test('view profile does not notify free profile owner', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $viewer = User::factory()->create(['subscription_plan' => 'silver']);
    Profile::factory()->create(['user_id' => $viewer->id]);
    $viewerSubscription = \App\Models\Subscription::factory()->active()->create([
        'user_id'    => $viewer->id,
        'plan'       => 'silver',
        'amount_bdt' => 499,
    ]);
    $viewer->update([
        'active_subscription_id'  => $viewerSubscription->id,
        'subscription_expires_at' => $viewerSubscription->expires_at,
    ]);

    $viewed = User::factory()->create(['subscription_plan' => 'free']);
    Profile::factory()->create(['user_id' => $viewed->id, 'profile_id' => 'BON-FREENOTIFY']);

    $this->actingAs($viewer)->getJson('/api/v1/profile/BON-FREENOTIFY');

    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $viewed->id,
        'type'          => 'profile_viewed',
    ]);

    \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\SendProfileViewedEmail::class);
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

