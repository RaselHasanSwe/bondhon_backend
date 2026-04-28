<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Registration ────────────────────────────────────────────────────────────

test('user can register with valid data', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Rahim Uddin',
        'email'                 => 'rahim@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'gender'                => 'male',
        'profile_created_by'    => 'self',
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure([
                 'success', 'data' => ['token', 'token_type', 'user' => ['id', 'name', 'email', 'profile']],
                 'message', 'errors',
             ])
             ->assertJson(['success' => true]);

    $this->assertDatabaseHas('users', ['email' => 'rahim@example.com']);
    $this->assertDatabaseHas('profiles', ['user_id' => User::where('email', 'rahim@example.com')->first()->id]);
});

test('registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'rahim@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Rahim Uddin',
        'email'                 => 'rahim@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'gender'                => 'male',
        'profile_created_by'    => 'self',
    ]);

    $response->assertStatus(422)->assertJson(['success' => false]);
});

test('registration fails with invalid gender', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Test',
        'email'                 => 'test@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'gender'                => 'other',
        'profile_created_by'    => 'self',
    ]);

    $response->assertStatus(422)->assertJson(['success' => false]);
});

test('registration fails with short password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Test',
        'email'                 => 'test@example.com',
        'password'              => 'short',
        'password_confirmation' => 'short',
        'gender'                => 'male',
        'profile_created_by'    => 'self',
    ]);

    $response->assertStatus(422);
});

test('registration auto-generates profile_id in BON-XXXXXX format', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'New User',
        'email'                 => 'new@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'gender'                => 'female',
        'profile_created_by'    => 'parents',
    ]);

    $user    = User::where('email', 'new@example.com')->first();
    $profile = Profile::where('user_id', $user->id)->first();

    expect($profile->profile_id)->toMatch('/^BON-\d{6}$/');
});

// ─── Login ───────────────────────────────────────────────────────────────────

test('user can login with valid credentials', function () {
    $user = User::factory()->create(['email' => 'user@test.com', 'password' => bcrypt('password123')]);
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'user@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure(['success', 'data' => ['token', 'token_type', 'user']])
             ->assertJson(['success' => true]);
});

test('login fails with wrong password', function () {
    User::factory()->create(['email' => 'user@test.com', 'password' => bcrypt('correct')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'user@test.com',
        'password' => 'wrong',
    ]);

    $response->assertStatus(401)->assertJson(['success' => false]);
});

test('banned user cannot login', function () {
    User::factory()->banned()->create(['email' => 'banned@test.com', 'password' => bcrypt('password123')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'banned@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)->assertJson(['success' => false]);
});

test('inactive user cannot login', function () {
    User::factory()->inactive()->create(['email' => 'inactive@test.com', 'password' => bcrypt('password123')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'inactive@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)->assertJson(['success' => false]);
});

// ─── Logout ──────────────────────────────────────────────────────────────────

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                     ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('unauthenticated request to logout returns 401', function () {
    $response = $this->postJson('/api/v1/auth/logout');
    $response->assertStatus(401);
});

// ─── Me ──────────────────────────────────────────────────────────────────────

test('authenticated user can get their own details', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
             ->assertJsonStructure(['success', 'data' => ['id', 'name', 'email', 'profile']])
             ->assertJson(['success' => true, 'data' => ['id' => $user->id]]);
});

test('me endpoint does not expose password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

    $data = $response->json('data');
    expect($data)->not->toHaveKey('password');
});

// ─── Email Verification ──────────────────────────────────────────────────────

test('unverified user gets 403 on protected routes', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/profile');

    $response->assertStatus(403);
});

test('verified user can access protected profile route', function () {
    $user = User::factory()->create(); // email_verified_at is set by default in factory
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/profile');

    $response->assertStatus(200);
});

test('can resend email verification', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/auth/email/resend');

    $response->assertStatus(200)->assertJson(['success' => true]);
});

test('resend verification returns error if already verified', function () {
    $user = User::factory()->create(); // verified by default

    $response = $this->actingAs($user)->postJson('/api/v1/auth/email/resend');

    $response->assertStatus(400)->assertJson(['success' => false]);
});

// ─── Password Reset ──────────────────────────────────────────────────────────

test('forgot password endpoint returns success even for non-existent email', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
});

test('forgot password fails without email field', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot', []);

    $response->assertStatus(422);
});

test('reset password fails with invalid token', function () {
    $user = User::factory()->create(['email' => 'user@reset.com']);

    $response = $this->postJson('/api/v1/auth/password/reset', [
        'token'                 => 'invalid-token',
        'email'                 => 'user@reset.com',
        'password'              => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422)->assertJson(['success' => false]);
});

