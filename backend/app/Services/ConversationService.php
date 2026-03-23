<?php
namespace App\Services;

use App\Models\Conversation;

interface ConversationService
{
    public function listConversations(int $userID): array;
    public function createConversation(int $userID, int $otherUserID): array;
    public function getConversationForViewer(int $userID, int $conversationID): array;
    public function getOtherUserPhotos(int $userID, int $conversationID): array;
    public function deleteConversation(int $userID, int $conversationID): ?string;
    public function formatConversationSummary(int $conversationID, int $viewerUserID): array;
}
