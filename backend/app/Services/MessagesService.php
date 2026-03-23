<?php
namespace App\Services;

use App\Models\Message;

interface MessagesService
{
    public function loadMessagesPage(int $userID, int $conversationID, int $cursor): array;
    public function sendMessage(int $userID, int $conversationID, string $text): array;
    public function markAsRead(int $userID, array $messageIDs): int;
    public function deleteMessage(int $userID, int $messageID): ?string;
    public function messageToArray(Message $message): array;
}
