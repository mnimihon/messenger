<?php
namespace App\Repositories;

use App\DTO\MessageDTO;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;

interface MessagesRepository
{
    public function find(int $messageID): ?Message;
    public function getAll($limit = 10): Collection;
    public function create(MessageDTO $dto): Message;
    public function update(int $messageID, MessageDTO $dto): Message;
    public function delete(int $messageID): bool;
    public function setIsReadAll(int $userID, int $conversationID): int;
    public function getByConversation(int $conversationID, int $cursor): Collection;
    public function markAsRead(int $userID, array $messageIDs): int;
}
