<?php
namespace App\Repositories\Implementations;

use App\DTO\ConversationCreateDTO;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\ConversationRepository;

class ConversationRepositoryImpl implements ConversationRepository {

    public function __construct(
        private readonly Conversation $model
    ) {}

    public function getConversations(int $userID): array
    {
        $user = User::find($userID);
        return $this->model->where('user1_id', $userID)
            ->orWhere('user2_id', $userID)
            ->with(['user1', 'user2', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->withCount(['messages as unread_count' => function($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                    ->where('is_read', false);
            }])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function($conversation) use ($user) {
                $otherUser = $conversation->otherUser($user->id);
                $lastMessage = $conversation->messages->first();

                return [
                    'id' => $conversation->id,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                    ],
                    'last_message' => $lastMessage ? [
                        'message' => $lastMessage->message,
                        'created_at' => $lastMessage->created_at,
                        'is_read' => $lastMessage->is_read,
                    ] : null,
                    'unread_count' => $conversation->unread_count,
                    'updated_at' => $conversation->updated_at,
                ];
            });
    }

    public function isConversationExists(int $userID, int $otherUserID): bool
    {
        $user = User::find($userID);
        return $this->model->where(function($query) use ($user, $otherUserID) {
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
        return $this->model->create($conversationCreateDTO->toDatabaseArray());
    }
}
