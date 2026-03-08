<?php
namespace App\Services;

use App\Models\Conversation;

interface ConversationService
{

    public function getConversations(int $userID): array;
    public function isConversationExists(int $userID, int $otherUserID): bool;
    public function createConversation(int $userID, int $otherUserID): Conversation;
}
