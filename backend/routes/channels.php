<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

//// Добавь это:
//Broadcast::channel('conversation.{id}', function ($user, $id) {
//    $conversation = Conversation::find($id);
//
//    if (!$conversation) return false;
//
//    // Проверяем что пользователь участник диалога
//    return $user->id === $conversation->user1_id ||
//        $user->id === $conversation->user2_id;
//});
