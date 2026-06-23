<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels — MyBouma Matrimony Platform
|--------------------------------------------------------------------------
*/

// Default Laravel user channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel per user (notifications, calls, online status)
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// Private conversation channel (messaging)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    return $conversation && (
        $conversation->user_one_id === $user->id ||
        $conversation->user_two_id === $user->id
    );
});

