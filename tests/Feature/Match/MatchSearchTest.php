<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('free user can search by profile id without subscription gate', function () {
    $searcher = User::factory()->create([
        'gender' => 'male',
        'subscription_plan' => 'free',
        'subscription_expires_at' => null,
    ]);
    Profile::factory()->create(['user_id' => $searcher->id]);

    $target = User::factory()->create([
        'gender' => 'female',
        'subscription_plan' => 'free',
    ]);
    Profile::factory()->create([
        'user_id' => $target->id,
        'profile_id' => 'BON-900001',
    ]);

    $response = $this->actingAs($searcher)->getJson('/api/v1/matches/search?profile_id=BON-900001');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total', 1);
});

test('free user can use advanced search filters without subscription gate', function () {
    $searcher = User::factory()->create([
        'gender' => 'male',
        'subscription_plan' => 'free',
        'subscription_expires_at' => null,
    ]);
    Profile::factory()->create(['user_id' => $searcher->id]);

    $target = User::factory()->create([
        'gender' => 'female',
        'subscription_plan' => 'free',
    ]);
    Profile::factory()->create(['user_id' => $target->id]);

    $response = $this->actingAs($searcher)->getJson('/api/v1/matches/search?income_min=100000&caste=general');

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('free user can fetch compatibility score endpoint without subscription gate', function () {
    $viewer = User::factory()->create([
        'gender' => 'male',
        'subscription_plan' => 'free',
        'subscription_expires_at' => null,
    ]);
    Profile::factory()->create(['user_id' => $viewer->id]);

    $candidate = User::factory()->create(['gender' => 'female']);
    Profile::factory()->create(['user_id' => $candidate->id]);

    $response = $this->actingAs($viewer)->getJson('/api/v1/matches/' . $candidate->id . '/compatibility');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['score', 'score_breakdown', 'calculated_at'],
        ]);
});

test('free user viewing another profile receives compatibility score', function () {
    $viewer = User::factory()->create([
        'gender' => 'male',
        'subscription_plan' => 'free',
        'subscription_expires_at' => null,
    ]);
    Profile::factory()->create(['user_id' => $viewer->id]);

    $other = User::factory()->create(['gender' => 'female']);
    Profile::factory()->create([
        'user_id' => $other->id,
        'profile_id' => 'BON-FREECTX',
    ]);

    $response = $this->actingAs($viewer)->getJson('/api/v1/profile/BON-FREECTX');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'compatibility_score' => ['score', 'score_breakdown', 'calculated_at'],
            ],
        ]);
});
