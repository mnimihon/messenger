<?php
namespace App\Services;

use App\Models\Conversation;

interface ConversationService
{
    public function createConversation(int $userID, int $otherUserID): Conversation;
}
