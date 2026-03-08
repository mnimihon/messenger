<?php
namespace App\Services;

use App\DTO\MessageDTO;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface MessagesService
{
    public function getByID(int $id);
    public function create(MessageDTO $dto): Message;
    public function update(Message $message, MessageDTO $dto): Message;
    public function delete(Message $message): bool;
    public function setIsReadAll(int $userID, Conversation $conversation): int;
    public function getByConversation(Conversation $conversation, int $cursor): array;
}
