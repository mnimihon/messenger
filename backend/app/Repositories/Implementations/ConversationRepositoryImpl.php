<?php
namespace App\Repositories\Implementations;

use App\DTO\ConversationCreateDTO;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\ConversationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ConversationRepositoryImpl implements ConversationRepository
{
    public function __construct(
        private readonly Conversation $model,
        private readonly User $userModel,
    ) {}

    public function getConversationsForUser(int $userID): Collection
    {
        $user = $this->userModel->newQuery()->findOrFail($userID);
        return $this->model->newQuery()
            ->where('user1_id', $userID)
            ->orWhere('user2_id', $userID)
            ->with(['user1', 'user2', 'user1.mainPhoto', 'user2.mainPhoto', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->withCount(['messages as unread_count' => function($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                    ->where('is_read', false);
            }])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function isConversationExists(int $userID, int $otherUserID): bool
    {
        $user = $this->userModel->newQuery()->find($userID);
        return $this->model->newQuery()
            ->where(function($query) use ($user, $otherUserID) {
                $query->where('user1_id', $user->id)
                    ->where('user2_id', $otherUserID);
            })
            ->orWhere(function($query) use ($user, $otherUserID) {
                $query->where('user1_id', $otherUserID)
                    ->where('user2_id', $user->id);
            })
            ->exists();
    }

    public function createConversation(ConversationCreateDTO $conversationCreateDTO): Conversation
    {
        return $this->model->newQuery()->create($conversationCreateDTO->toDatabaseArray());
    }

    public function softDeleteWithMessages(Conversation $conversation): void
    {
        DB::transaction(function () use ($conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        });
    }
}
