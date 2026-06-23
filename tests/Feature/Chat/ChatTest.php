<?php

use App\Models\Block;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent;
use App\Events\TypingEvent;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeUser(array $attrs = []): User
{
    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'subscription_plan' => 'free',
    ], $attrs));

    Profile::factory()->create(['user_id' => $user->id]);

    return $user;
}

function makeMutualInterest(User $a, User $b): Interest
{
    return Interest::create([
        'sender_id'   => $a->id,
        'receiver_id' => $b->id,
        'status'      => 'accepted',
        'expires_at'  => now()->addDays(30),
    ]);
}

function makeConversation(User $a, User $b): Conversation
{
    [$one, $two] = $a->id < $b->id ? [$a->id, $b->id] : [$b->id, $a->id];
    return Conversation::create([
        'user_one_id' => $one,
        'user_two_id' => $two,
    ]);
}

function actingAsUser(User $user): array
{
    $token = $user->createToken('test')->plainTextToken;
    return ['Authorization' => 'Bearer ' . $token];
}

// ─── Conversations ───────────────────────────────────────────────────────────

describe('Conversations', function () {

    it('returns empty conversations list for new user', function () {
        $user    = makeUser();
        $headers = actingAsUser($user);

        $res = $this->getJson('/api/v1/conversations', $headers);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 0);
    });

    it('lists conversations sorted by last message', function () {
        $user  = makeUser();
        $other = makeUser();
        makeMutualInterest($user, $other);
        $conv = makeConversation($user, $other);

        $headers = actingAsUser($user);
        $res     = $this->getJson('/api/v1/conversations', $headers);

        $res->assertOk()
            ->assertJsonPath('data.pagination.total', 1);
    });

    it('get or create conversation requires mutual interest', function () {
        $user  = makeUser();
        $other = makeUser();

        $headers = actingAsUser($user);
        $res     = $this->postJson('/api/v1/conversations', ['user_id' => $other->id], $headers);

        $res->assertStatus(403)
            ->assertJsonPath('success', false);
    });

    it('get or create conversation works with mutual interest', function () {
        $user  = makeUser();
        $other = makeUser();
        makeMutualInterest($user, $other);

        $headers = actingAsUser($user);
        $res     = $this->postJson('/api/v1/conversations', ['user_id' => $other->id], $headers);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'participant', 'unread_count']]);
    });

    it('get or create is idempotent', function () {
        $user  = makeUser();
        $other = makeUser();
        makeMutualInterest($user, $other);

        $headers = actingAsUser($user);
        $this->postJson('/api/v1/conversations', ['user_id' => $other->id], $headers)->assertOk();
        $this->postJson('/api/v1/conversations', ['user_id' => $other->id], $headers)->assertOk();

        expect(Conversation::count())->toBe(1);
    });

    it('cannot create conversation with blocked user', function () {
        $user  = makeUser();
        $other = makeUser();
        makeMutualInterest($user, $other);
        Block::create(['blocker_id' => $user->id, 'blocked_id' => $other->id]);

        $headers = actingAsUser($user);
        $res     = $this->postJson('/api/v1/conversations', ['user_id' => $other->id], $headers);

        $res->assertStatus(403);
    });
});

// ─── Messages ────────────────────────────────────────────────────────────────

describe('Messages', function () {

    it('can retrieve messages for a conversation', function () {
        $user  = makeUser();
        $other = makeUser();
        $conv  = makeConversation($user, $other);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'type'            => 'text',
            'body'            => 'Hello world',
            'delivered_at'    => now(),
        ]);

        $headers = actingAsUser($user);
        $res     = $this->getJson("/api/v1/conversations/{$conv->id}/messages", $headers);

        $res->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.body', 'Hello world');
    });

    it('can send a text message', function () {
        Event::fake([MessageSent::class]);

        $user  = makeUser();
        $other = makeUser();
        makeMutualInterest($user, $other);
        $conv = makeConversation($user, $other);

        $headers = actingAsUser($user);
        $res     = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'text',
            'body' => 'Hey there! 👋',
        ], $headers);

        $res->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'text')
            ->assertJsonPath('data.body', 'Hey there! 👋')
            ->assertJsonPath('data.sender_id', $user->id);

        Event::assertDispatched(MessageSent::class);

        expect(Message::count())->toBeGreaterThan(0);
    });

    it('validates text message requires body', function () {
        $user = makeUser();
        $conv = makeConversation($user, makeUser());

        $headers = actingAsUser($user);
        $res     = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'text',
        ], $headers);

        $res->assertStatus(422)
            ->assertJsonPath('success', false);
    });

    it('can upload an image message', function () {
        Event::fake([MessageSent::class]);
        Storage::fake('public');

        $user  = makeUser();
        $other = makeUser();
        makeMutualInterest($user, $other);
        $conv = makeConversation($user, $other);

        $file    = UploadedFile::fake()->image('test.jpg', 100, 100);
        $headers = actingAsUser($user);

        $res = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'image',
            'file' => $file,
        ], $headers);

        $res->assertCreated()
            ->assertJsonPath('data.type', 'image')
            ->assertJsonPath('data.file_mime_type', 'image/jpeg');

        Event::assertDispatched(MessageSent::class);
    });

    it('can upload a document message (pdf)', function () {
        Event::fake([MessageSent::class]);
        Storage::fake('public');

        $user  = makeUser();
        $other = makeUser();
        makeMutualInterest($user, $other);
        $conv = makeConversation($user, $other);

        $file    = UploadedFile::fake()->create('report.pdf', 500, 'application/pdf');
        $headers = actingAsUser($user);

        $res = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'document',
            'file' => $file,
        ], $headers);

        $res->assertCreated()
            ->assertJsonPath('data.type', 'document')
            ->assertJsonPath('data.file_name', 'report.pdf');
    });

    it('rejects unsupported file type', function () {
        Storage::fake('public');

        $user  = makeUser();
        $other = makeUser();
        $conv  = makeConversation($user, $other);

        $file    = UploadedFile::fake()->create('virus.exe', 100, 'application/exe');
        $headers = actingAsUser($user);

        $res = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'document',
            'file' => $file,
        ], $headers);

        $res->assertStatus(422);
    });

    it('can send a reply to another message', function () {
        Event::fake([MessageSent::class]);

        $user  = makeUser();
        $other = makeUser();
        makeMutualInterest($user, $other);
        $conv = makeConversation($user, $other);

        $original = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $other->id,
            'type'            => 'text',
            'body'            => 'Original message',
            'delivered_at'    => now(),
        ]);

        $headers = actingAsUser($user);
        $res     = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type'                => 'text',
            'body'                => 'This is a reply',
            'reply_to_message_id' => $original->id,
        ], $headers);

        $res->assertCreated()
            ->assertJsonPath('data.reply_to_message_id', $original->id);
    });

    it('can mark conversation as read', function () {
        $user  = makeUser();
        $other = makeUser();
        $conv  = makeConversation($user, $other);

        // Create unread messages from other user
        Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $other->id,
            'type'            => 'text',
            'body'            => 'Unread msg',
            'delivered_at'    => now(),
        ]);

        $headers = actingAsUser($user);
        $res     = $this->putJson("/api/v1/conversations/{$conv->id}/read", [], $headers);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.updated', 1);

        expect(Message::whereNull('read_at')->count())->toBe(0);
    });

    it('can soft-delete own message', function () {
        $user  = makeUser();
        $other = makeUser();
        $conv  = makeConversation($user, $other);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'type'            => 'text',
            'body'            => 'Delete me',
            'delivered_at'    => now(),
        ]);

        $headers = actingAsUser($user);
        $res     = $this->deleteJson("/api/v1/messages/{$msg->id}", [], $headers);

        $res->assertOk();
        expect($msg->fresh()->is_deleted)->toBeTrue();
    });

    it('cannot delete another user message', function () {
        $user  = makeUser();
        $other = makeUser();
        $conv  = makeConversation($user, $other);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $other->id,
            'type'            => 'text',
            'body'            => 'Not mine',
            'delivered_at'    => now(),
        ]);

        $headers = actingAsUser($user);
        $res     = $this->deleteJson("/api/v1/messages/{$msg->id}", [], $headers);

        $res->assertStatus(403);
    });

    it('cannot access messages of another user conversation', function () {
        $user  = makeUser();
        $other = makeUser();
        $conv  = makeConversation($other, makeUser());

        $headers = actingAsUser($user);
        $res     = $this->getJson("/api/v1/conversations/{$conv->id}/messages", $headers);

        $res->assertStatus(403);
    });

    it('deleted message body is hidden in response', function () {
        $user = makeUser();
        $conv = makeConversation($user, makeUser());

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'type'            => 'text',
            'body'            => null,
            'is_deleted'      => true,
            'delivered_at'    => now(),
        ]);

        $headers = actingAsUser($user);
        $res     = $this->getJson("/api/v1/conversations/{$conv->id}/messages", $headers);

        $res->assertOk()
            ->assertJsonPath('data.data.0.body', null)
            ->assertJsonPath('data.data.0.is_deleted', true);
    });
});

// ─── Typing indicator ────────────────────────────────────────────────────────

describe('Typing indicator', function () {

    it('can broadcast typing status', function () {
        Event::fake([TypingEvent::class]);

        $user  = makeUser();
        $conv  = makeConversation($user, makeUser());
        $headers = actingAsUser($user);

        $res = $this->postJson("/api/v1/conversations/{$conv->id}/typing", [
            'is_typing' => true,
        ], $headers);

        $res->assertOk();
        Event::assertDispatched(TypingEvent::class, fn ($e) => $e->isTyping === true);
    });
});

// ─── Notifications ────────────────────────────────────────────────────────────

describe('Notifications', function () {

    it('returns empty notifications for new user', function () {
        $user    = makeUser();
        $headers = actingAsUser($user);

        $res = $this->getJson('/api/v1/notifications', $headers);

        $res->assertOk()
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.pagination.total', 0);
    });

    it('can mark a notification as read', function () {
        $user = makeUser();
        $notif = $user->notifications()->create([
            'id'      => \Illuminate\Support\Str::uuid(),
            'type'    => 'test_notification',
            'data'    => json_encode(['message' => 'Hello']),
            'is_read' => false,
        ]);

        $headers = actingAsUser($user);
        $res     = $this->putJson("/api/v1/notifications/{$notif->id}/read", [], $headers);

        $res->assertOk();
        expect((bool) $notif->fresh()->is_read)->toBeTrue();
    });

    it('can mark all notifications as read', function () {
        $user = makeUser();
        foreach (range(1, 3) as $i) {
            $user->notifications()->create([
                'id'      => \Illuminate\Support\Str::uuid(),
                'type'    => 'test_type',
                'data'    => json_encode(['msg' => $i]),
                'is_read' => false,
            ]);
        }

        $headers = actingAsUser($user);
        $res     = $this->putJson('/api/v1/notifications/read-all', [], $headers);

        $res->assertOk()
            ->assertJsonPath('data.updated', 3);

        expect($user->notifications()->where('is_read', false)->count())->toBe(0);
    });

    it('can delete a notification', function () {
        $user = makeUser();
        $notif = $user->notifications()->create([
            'id'      => \Illuminate\Support\Str::uuid(),
            'type'    => 'test_type',
            'data'    => json_encode(['msg' => 'test']),
            'is_read' => false,
        ]);

        $headers = actingAsUser($user);
        $res     = $this->deleteJson("/api/v1/notifications/{$notif->id}", [], $headers);

        $res->assertOk();
        expect($user->notifications()->count())->toBe(0);
    });

    it('returns unread count', function () {
        $user = makeUser();
        $user->notifications()->create([
            'id'      => \Illuminate\Support\Str::uuid(),
            'type'    => 'test',
            'data'    => json_encode([]),
            'is_read' => false,
        ]);

        $headers = actingAsUser($user);
        $res     = $this->getJson('/api/v1/notifications/unread-count', $headers);

        $res->assertOk()
            ->assertJsonPath('data.count', 1);
    });
});

