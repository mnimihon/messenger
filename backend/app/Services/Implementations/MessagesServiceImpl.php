<?php
namespace App\Services\Implementations;

use App\DTO\MessageDTO;
use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\MessagesRepository;
use App\Services\MessagesService;
use Illuminate\Database\Eloquent\Collection;

class MessagesServiceImpl implements MessagesService {

    public function __construct(
        private readonly MessagesRepository $repository
    ) {}

    public function getByID(int $id) {
        return $this->repository->getByID($id);
    }

    public function create(MessageDTO $dto): Message
    {
        return $this->repository->create($dto);
    }

    public function update(Message $message, MessageDTO $dto): Message
    {
        return $this->repository->update($message, $dto);
    }

    public function delete(Message $message): bool
    {
        return $this->repository->delete($message);
    }

    public function getAll(int $limit = 10): Collection
    {
        return $this->repository->getAll($limit);
    }

    public function setIsReadAll(int $userID, Conversation $conversation): Message
    {
        return $this->repository->setIsReadAll($userID, $conversation);
    }

    public function getByConversation(Conversation $conversation, int $cursor): array
    {
        $messages = $this->repository->getByConversation($conversation, $cursor);
        $nextCursor = $messages->isNotEmpty() ? $messages->last()->id : null;

        return [
            'messages' => $messages,
            'next_cursor' => $nextCursor,
        ];
    }
}
