<?php
namespace App\Repositories\Implementations;

use App\DTO\MessageDTO;
use App\Models\Message;
use App\Models\User;
use App\Repositories\MessagesRepository;
use Illuminate\Database\Eloquent\Collection;

class MessagesRepositoryImpl implements MessagesRepository
{
    public function __construct(
        private readonly Message $model,
        private readonly User $userModel,
    ) {}

    public function find(int $messageID): ?Message
    {
        return $this->model->newQuery()->find($messageID);
    }

    public function getAll($limit = 10): Collection
    {
        return $this->model->newQuery()->take($limit)->get();
    }

    public function create(MessageDTO $dto): Message
    {
        return $this->model->newQuery()->create($dto->toDatabaseArray());
    }

    public function update(int $messageID, MessageDTO $dto): Message
    {
        $message = $this->model->newQuery()->findOrFail($messageID);
        $message->update($dto->toDatabaseArray());
        return $message;
    }

    public function delete(int $messageID): bool
    {
        $message = $this->model->newQuery()->findOrFail($messageID);
        return $message->delete();
    }

    public function setIsReadAll(int $userID, int $conversationID): int
    {
        return $this->model->newQuery()
            ->where('conversation_id', $conversationID)
            ->where('sender_id', '!=', $userID)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function getByConversation(int $conversationID, int $cursor): Collection
    {
        $query = $this->model->newQuery()
            ->where('conversation_id', $conversationID)
            ->with('sender:id,name')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(50);

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }
        return $query->get();
    }

    public function markAsRead(int $userID, array $messageIDs): int
    {
        $user = $this->userModel->newQuery()->find($userID);
        return $this->model->newQuery()
            ->whereIn('id', $messageIDs)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', 0)
            ->whereHas('conversation', function ($q) use ($user) {
                $q->where('user1_id', $user->id)
                    ->orWhere('user2_id', $user->id);
            })
            ->update(['is_read' => 1]);
    }
}
