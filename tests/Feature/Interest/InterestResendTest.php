<?php

use App\Models\Interest;
use App\Models\Profile;
use App\Models\User;
use App\Services\InterestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function createInterestUser(string $suffix = 'A'): User
{
    $user = User::factory()->create(['name' => 'User ' . $suffix]);
    Profile::factory()->create(['user_id' => $user->id]);

    return $user;
}

function createPendingInterest(User $sender, User $receiver, int $sendCount = 1): Interest
{
    return Interest::create([
        'sender_id'   => $sender->id,
        'receiver_id' => $receiver->id,
        'status'      => 'pending',
        'send_count'  => $sendCount,
        'expires_at'  => now()->addDays(30),
    ]);
}

test('user can resend interest after previous interest was declined', function () {
    Event::fake();

    $sender = createInterestUser('Sender');
    $receiver = createInterestUser('Receiver');
    $interest = createPendingInterest($sender, $receiver);

    $this->actingAs($receiver)->putJson("/api/v1/interests/{$interest->id}/decline")
        ->assertStatus(200);

    $response = $this->actingAs($sender)->postJson('/api/v1/interests', [
        'receiver_id' => $receiver->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('interests', [
        'id'         => $interest->id,
        'send_count' => 2,
        'status'     => 'pending',
    ]);
});

test('user cannot resend interest after reaching max send attempts', function () {
    $sender = createInterestUser('Sender');
    $receiver = createInterestUser('Receiver');
    $maxAttempts = app(InterestService::class)->maxSendAttempts();

    $interest = createPendingInterest($sender, $receiver, $maxAttempts);
    $interest->update(['status' => 'declined']);

    $response = $this->actingAs($sender)->postJson('/api/v1/interests', [
        'receiver_id' => $receiver->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.max_attempts', $maxAttempts);
});

test('interest status returns declined after max attempts exhausted', function () {
    $sender = createInterestUser('Sender');
    $receiver = createInterestUser('Receiver');
    $maxAttempts = app(InterestService::class)->maxSendAttempts();

    $interest = createPendingInterest($sender, $receiver, $maxAttempts);
    $interest->update(['status' => 'ignored']);

    $this->actingAs($sender)->getJson("/api/v1/interests/status/{$receiver->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'ignored')
        ->assertJsonPath('data.is_sender', true)
        ->assertJsonPath('data.can_send_interest', false);
});

test('interest status returns none when sender can resend after decline', function () {
    $sender = createInterestUser('Sender');
    $receiver = createInterestUser('Receiver');

    $interest = createPendingInterest($sender, $receiver, 1);
    $interest->update(['status' => 'declined']);

    $this->actingAs($sender)->getJson("/api/v1/interests/status/{$receiver->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'none')
        ->assertJsonPath('data.can_send_interest', true);
});

test('sender can resend after decline when outgoing interest is declined not pending', function () {
    Event::fake();

    $sender = createInterestUser('Sender');
    $receiver = createInterestUser('Receiver');

    $interest = createPendingInterest($sender, $receiver, 1);
    $interest->update(['status' => 'declined']);

    $this->actingAs($sender)->postJson('/api/v1/interests', [
        'receiver_id' => $receiver->id,
    ])->assertStatus(201)
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('interests', [
        'id'         => $interest->id,
        'send_count' => 2,
        'status'     => 'pending',
    ]);
});

test('user can send interest in opposite direction after declining received interest', function () {
    Event::fake();

    $sender = createInterestUser('Sender');
    $receiver = createInterestUser('Receiver');
    $interest = createPendingInterest($sender, $receiver);

    $this->actingAs($receiver)->putJson("/api/v1/interests/{$interest->id}/decline")
        ->assertStatus(200);

    $this->actingAs($receiver)->postJson('/api/v1/interests', [
        'receiver_id' => $sender->id,
    ])->assertStatus(201);
});
