<?php
namespace App\Services;

use App\DTO\ConversationCreateDTO;
use App\Models\Conversation;
use App\Models\User;

interface ConversationService
{

    public function getConversations(int $userID): array;
    public function isConversationExists(int $userID, int $otherUserID): bool;
    public function createConversation(int $userID, int $otherUserID): Conversation;
    public function isAccessConversation(int $userID, Conversation $conversation): bool;
}
