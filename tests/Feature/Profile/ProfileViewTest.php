<?php

use App\Models\Interest;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPaidUserWithFeature(string $plan = 'silver', array $features = []): User
{
    $planModel = SubscriptionPlan::create([
        'name'           => ucfirst($plan) . ' Plan',
        'slug'           => $plan . '-1-month-test',
        'description'    => 'Test plan',
        'plan_type'      => $plan,
        'price_bdt'      => 499,
        'duration_qty'   => 1,
        'duration_unit'  => 'month',
        'features'       => $features,
        'is_active'      => true,
        'sort_order'     => 1,
    ]);

    $user = User::factory()->create(['subscription_plan' => $plan]);
    Profile::factory()->create(['user_id' => $user->id]);

    $subscription = Subscription::factory()->active()->create([
        'user_id'              => $user->id,
        'subscription_plan_id' => $planModel->id,
        'plan'                 => $plan,
        'amount_bdt'           => 499,
    ]);

    $user->update([
        'active_subscription_id'  => $subscription->id,
        'subscription_expires_at' => $subscription->expires_at,
    ]);

    return $user->fresh();
}

test('profile views endpoint returns viewers with connection status', function () {
    $owner = createPaidUserWithFeature('silver', ['see_who_viewed_profile' => true]);
    $viewer = User::factory()->create();
    Profile::factory()->create(['user_id' => $viewer->id]);

    ProfileView::create([
        'viewer_id' => $viewer->id,
        'viewed_id' => $owner->id,
        'viewed_at' => now(),
    ]);

    Interest::factory()->create([
        'sender_id'   => $owner->id,
        'receiver_id' => $viewer->id,
        'status'      => 'accepted',
    ]);

    $response = $this->actingAs($owner)->getJson('/api/v1/profile-views');

    $response->assertStatus(200)
        ->assertJsonPath('data.data.0.connection_status', 'accepted')
        ->assertJsonPath('data.data.0.is_interest_sender', true)
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    ['viewer_id', 'viewed_at', 'connection_status', 'interest_id', 'is_interest_sender', 'conversation_id', 'viewer'],
                ],
            ],
        ]);
});

test('profile views endpoint returns received pending interest metadata', function () {
    $owner = createPaidUserWithFeature('silver', ['see_who_viewed_profile' => true]);
    $viewer = User::factory()->create();
    Profile::factory()->create(['user_id' => $viewer->id]);

    ProfileView::create([
        'viewer_id' => $viewer->id,
        'viewed_id' => $owner->id,
        'viewed_at' => now(),
    ]);

    $interest = Interest::factory()->create([
        'sender_id'   => $viewer->id,
        'receiver_id' => $owner->id,
        'status'      => 'pending',
    ]);

    $response = $this->actingAs($owner)->getJson('/api/v1/profile-views');

    $response->assertStatus(200)
        ->assertJsonPath('data.data.0.connection_status', 'pending')
        ->assertJsonPath('data.data.0.interest_id', $interest->id)
        ->assertJsonPath('data.data.0.is_interest_sender', false);
});

test('profile views endpoint supports search', function () {
    $owner = createPaidUserWithFeature('silver', ['see_who_viewed_profile' => true]);

    $viewer = User::factory()->create(['name' => 'Searchable Viewer']);
    Profile::factory()->create([
        'user_id'    => $viewer->id,
        'city'       => 'Dhaka',
    ]);

    $other = User::factory()->create(['name' => 'Someone Else']);
    Profile::factory()->create(['user_id' => $other->id]);

    ProfileView::create([
        'viewer_id' => $viewer->id,
        'viewed_id' => $owner->id,
        'viewed_at' => now(),
    ]);

    ProfileView::create([
        'viewer_id' => $other->id,
        'viewed_id' => $owner->id,
        'viewed_at' => now(),
    ]);

    $response = $this->actingAs($owner)->getJson('/api/v1/profile-views?search=Dhaka');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.viewer.name', 'Searchable Viewer');
});

test('profile views endpoint shows none connection status when no interest exists', function () {
    $owner = createPaidUserWithFeature('silver', ['see_who_viewed_profile' => true]);
    $viewer = User::factory()->create();
    Profile::factory()->create(['user_id' => $viewer->id]);

    ProfileView::create([
        'viewer_id' => $viewer->id,
        'viewed_id' => $owner->id,
        'viewed_at' => now(),
    ]);

    $response = $this->actingAs($owner)->getJson('/api/v1/profile-views');

    $response->assertStatus(200)
        ->assertJsonPath('data.data.0.connection_status', 'none');
});

test('profile views endpoint excludes self views', function () {
    $owner = createPaidUserWithFeature('silver', ['see_who_viewed_profile' => true]);

    ProfileView::create([
        'viewer_id' => $owner->id,
        'viewed_id' => $owner->id,
        'viewed_at' => now(),
    ]);

    $viewer = User::factory()->create();
    Profile::factory()->create(['user_id' => $viewer->id]);

    ProfileView::create([
        'viewer_id' => $viewer->id,
        'viewed_id' => $owner->id,
        'viewed_at' => now(),
    ]);

    $response = $this->actingAs($owner)->getJson('/api/v1/profile-views');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.data.0.viewer_id', $viewer->id);
});

test('free user cannot access profile views endpoint', function () {
    $owner = User::factory()->create(['subscription_plan' => 'free']);
    Profile::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($owner)->getJson('/api/v1/profile-views');

    $response->assertStatus(403)
        ->assertJsonPath('errors.feature', 'see_who_viewed_profile');
});
