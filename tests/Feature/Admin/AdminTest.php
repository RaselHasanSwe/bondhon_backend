<?php

use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAdmin(): User
{
    $admin = User::factory()->admin()->create();
    Profile::factory()->create(['user_id' => $admin->id]);
    return $admin;
}

function createVerifiedUser(): User
{
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);
    return $user;
}

// ─── Admin Access Control ─────────────────────────────────────────────────────

test('non-admin cannot access admin routes', function () {
    $user = createVerifiedUser();

    $response = $this->actingAs($user)->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(403);
});

test('unauthenticated user cannot access admin routes', function () {
    $response = $this->getJson('/api/v1/admin/dashboard');
    $response->assertStatus(401);
});

// ─── Admin Dashboard ──────────────────────────────────────────────────────────

test('admin can view dashboard stats', function () {
    $admin = createAdmin();

    // Create some users
    User::factory(3)->create();

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [
                     'total_users', 'active_today', 'pending_photos',
                     'pending_reports', 'new_users_today', 'verified_users',
                     'banned_users', 'active_subscriptions',
                 ],
             ])
             ->assertJson(['success' => true]);
});

// ─── Admin User Management ────────────────────────────────────────────────────

test('admin can list all users', function () {
    $admin = createAdmin();
    User::factory(5)->create();

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/users');

    $response->assertStatus(200)
             ->assertJsonStructure(['data' => ['data', 'total', 'per_page']])
             ->assertJson(['success' => true]);
});

test('admin can search users by name or email', function () {
    $admin  = createAdmin();
    $target = User::factory()->create(['name' => 'Unique Name XYZ', 'email' => 'unique@searchtest.com']);

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/users?search=Unique+Name+XYZ');

    $response->assertStatus(200);
    $data = $response->json('data.data');
    expect(collect($data)->pluck('name')->toArray())->toContain('Unique Name XYZ');
});

test('admin can ban a user', function () {
    $admin  = createAdmin();
    $target = User::factory()->create();

    $response = $this->actingAs($admin)->putJson('/api/v1/admin/users/' . $target->id . '/ban', [
        'is_banned' => true,
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('users', ['id' => $target->id, 'is_banned' => true]);
});

test('admin can unban a user', function () {
    $admin  = createAdmin();
    $target = User::factory()->banned()->create();

    $response = $this->actingAs($admin)->putJson('/api/v1/admin/users/' . $target->id . '/ban', [
        'is_banned' => false,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', ['id' => $target->id, 'is_banned' => false]);
});

test('admin can verify a user profile', function () {
    $admin  = createAdmin();
    $target = User::factory()->create();
    Profile::factory()->create(['user_id' => $target->id, 'is_verified' => false]);

    $response = $this->actingAs($admin)->putJson('/api/v1/admin/users/' . $target->id . '/verify');

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('profiles', ['user_id' => $target->id, 'is_verified' => true]);
});

test('admin verify fails for user without profile', function () {
    $admin  = createAdmin();
    $target = User::factory()->create(); // no profile

    $response = $this->actingAs($admin)->putJson('/api/v1/admin/users/' . $target->id . '/verify');

    $response->assertStatus(400)->assertJson(['success' => false]);
});

// ─── Admin Photo Moderation ───────────────────────────────────────────────────

test('admin can view pending photos', function () {
    $admin = createAdmin();
    $user  = createVerifiedUser();

    ProfilePhoto::factory(3)->create(['user_id' => $user->id, 'moderation_status' => 'pending']);

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/photos/pending');

    $response->assertStatus(200)->assertJson(['success' => true]);
    $data = $response->json('data.data');
    expect(count($data))->toBeGreaterThanOrEqual(3);
});

test('admin can approve a photo', function () {
    $admin = createAdmin();
    $user  = createVerifiedUser();
    $photo = ProfilePhoto::factory()->create(['user_id' => $user->id, 'moderation_status' => 'pending', 'is_approved' => false]);

    $response = $this->actingAs($admin)->putJson('/api/v1/admin/photos/' . $photo->id . '/approve');

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('profile_photos', ['id' => $photo->id, 'moderation_status' => 'approved', 'is_approved' => true]);
});

test('admin can reject a photo', function () {
    $admin = createAdmin();
    $user  = createVerifiedUser();
    $photo = ProfilePhoto::factory()->create(['user_id' => $user->id, 'moderation_status' => 'pending']);

    $response = $this->actingAs($admin)->putJson('/api/v1/admin/photos/' . $photo->id . '/reject', [
        'reason' => 'Inappropriate content',
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('profile_photos', ['id' => $photo->id, 'moderation_status' => 'rejected']);
});

// ─── Admin Reports ────────────────────────────────────────────────────────────

test('admin can list all reports', function () {
    $admin    = createAdmin();
    $reporter = createVerifiedUser();
    $reported = createVerifiedUser();

    Report::factory(3)->create([
        'reporter_id' => $reporter->id,
        'reported_id' => $reported->id,
    ]);

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/reports');

    $response->assertStatus(200)->assertJson(['success' => true]);
});

test('admin can filter reports by status', function () {
    $admin    = createAdmin();
    $reporter = createVerifiedUser();
    $reported = createVerifiedUser();

    Report::factory(2)->create([
        'reporter_id' => $reporter->id,
        'reported_id' => $reported->id,
        'status'      => 'pending',
    ]);

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/reports?status=pending');

    $response->assertStatus(200);
    $data = $response->json('data.data');
    foreach ($data as $report) {
        expect($report['status'])->toBe('pending');
    }
});

test('admin can take action on a report', function () {
    $admin    = createAdmin();
    $reporter = createVerifiedUser();
    $reported = createVerifiedUser();

    $report = Report::factory()->create([
        'reporter_id' => $reporter->id,
        'reported_id' => $reported->id,
        'status'      => 'pending',
    ]);

    $response = $this->actingAs($admin)->putJson('/api/v1/admin/reports/' . $report->id . '/action', [
        'status' => 'action_taken',
        'notes'  => 'User has been warned.',
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'action_taken']);
});

test('admin report action fails with invalid status', function () {
    $admin    = createAdmin();
    $reporter = createVerifiedUser();
    $reported = createVerifiedUser();
    $report   = Report::factory()->create(['reporter_id' => $reporter->id, 'reported_id' => $reported->id]);

    $response = $this->actingAs($admin)->putJson('/api/v1/admin/reports/' . $report->id . '/action', [
        'status' => 'invalid_status',
    ]);

    $response->assertStatus(422);
});

