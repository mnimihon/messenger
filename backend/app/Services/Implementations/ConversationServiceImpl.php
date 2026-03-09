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

    public function createConversation(int $userID, int $otherUserID): Conversation
    {
        $user1ID = min($userID, $otherUserID);
        $user2ID = max($userID, $otherUserID);
        return $this->repository->createConversation(new ConversationCreateDTO(
            userID: $user1ID,
            otherUserID: $user2ID
        ));
    }
}
