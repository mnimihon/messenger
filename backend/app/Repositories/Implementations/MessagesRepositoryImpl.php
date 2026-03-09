<?php
namespace App\Repositories\Implementations;

use App\DTO\MessageDTO;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\MessagesRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MessagesRepositoryImpl implements MessagesRepository {

    public function __construct(
        private readonly Message $model
    ) {}

    public function getAll($limit = 10): Collection
    {
        return $this->model->take($limit)->get();
    }

    public function create(MessageDTO $dto): Message
    {
        return $this->model->create($dto->toDatabaseArray());
    }

    public function update(Message $message, MessageDTO $dto): Message
    {
        $message->update($dto->toDatabaseArray());
        return $message;
    }

    public function delete(Message $message): bool
    {
        return $message->delete();
    }

    public function setIsReadAll(int $userID, Conversation $conversation): int
    {
        return Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userID)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function getByConversation(Conversation $conversation, int $cursor): Collection
    {
        $query = Message::where('conversation_id', $conversation->id)
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
        $user = User::find($userID);
        return Message::whereIn('id', $messageIDs)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', 0)
            ->whereHas('conversation', function ($q) use ($user) {
                $q->where('user1_id', $user->id)
                    ->orWhere('user2_id', $user->id);
            })
            ->update(['is_read' => 1]);
    }
}
