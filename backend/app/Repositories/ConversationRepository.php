<?php
namespace App\Repositories;

use App\DTO\ConversationCreateDTO;
use App\Models\Conversation;
use App\Models\User;

interface ConversationRepository
{

    public function getConversations(int $userID): array;
    public function isConversationExists(int $userID, int $otherUserID): bool;
    public function createConversation(ConversationCreateDTO $conversationCreateDTO): Conversation;
}
