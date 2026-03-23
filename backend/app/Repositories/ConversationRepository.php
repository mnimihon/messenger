<?php
namespace App\Repositories;

use App\DTO\ConversationCreateDTO;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Collection;

interface ConversationRepository
{
    public function getConversationsForUser(int $userID): Collection;
    public function isConversationExists(int $userID, int $otherUserID): bool;
    public function createConversation(ConversationCreateDTO $conversationCreateDTO): Conversation;
    public function softDeleteWithMessages(Conversation $conversation): void;
}
