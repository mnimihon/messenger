<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;


Broadcast::channel('conversation.{id}', function ($user, $id) {
    return true;
});
