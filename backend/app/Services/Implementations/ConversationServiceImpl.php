<?php
namespace App\Services\Implementations;

use App\DTO\ConversationCreateDTO;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\ConversationRepository;
use App\Services\ConversationService;

class ConversationServiceImpl implements ConversationService {

    public function __construct(
        private readonly ConversationRepository $repository
    ) {}

    public function getConversations(int $userID): array
    {
        return $this->repository->getConversations($userID);
    }

    public function isConversationExists(int $userID, int $otherUserID): bool
    {
        return $this->repository->isConversationExists($userID, $otherUserID);
    }

    public function createConversation($userID, $otherUserID): Conversation
    {
        $user1ID = min($userID, $otherUserID);
        $user2ID = max($userID, $otherUserID);
        return $this->repository->createConversation(new ConversationCreateDTO(
            userID: $user1ID,
            otherUserID: $user2ID
        ));
    }

    public function isAccessConversation(int $userID, Conversation $conversation): bool
    {
        $user = User::find($userID);
        return in_array($user->id, [$conversation->user1_id, $conversation->user2_id]);
    }
}
