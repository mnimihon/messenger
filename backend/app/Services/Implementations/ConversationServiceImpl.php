<?php
namespace App\Services\Implementations;

use App\DTO\ConversationCreateDTO;
use App\Http\Responses\ServiceResult;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\ConversationRepository;
use App\Services\ConversationService;

class ConversationServiceImpl implements ConversationService {

    public function __construct(
        private readonly ConversationRepository $repository,
        private readonly Conversation $conversationModel,
        private readonly User $userModel,
    ) {}

    public function listConversations(int $userID): array
    {
        $conversations = $this->repository->getConversationsForUser($userID);
        $user = $this->userModel->newQuery()->findOrFail($userID);

        return $conversations->map(function (Conversation $conversation) use ($user) {
            $otherUser = $conversation->otherUser($user->id);
            $lastMessage = $conversation->messages->first();

            return [
                'id' => $conversation->id,
                'other_user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'avatar_url' => $otherUser->mainPhoto?->url,
                ],
                'last_message' => $lastMessage ? [
                    'message' => $lastMessage->message,
                    'created_at' => $lastMessage->created_at,
                    'is_read' => $lastMessage->is_read,
                ] : null,
                'unread_count' => $conversation->unread_count,
                'updated_at' => $conversation->updated_at,
            ];
        })->values()->all();
    }

    public function createConversation(int $userID, int $otherUserID): array
    {
        if ($userID === $otherUserID) {
            return ['type' => ServiceResult::TYPE_SELF];
        }

        if ($this->repository->isConversationExists($userID, $otherUserID)) {
            return ['type' => ServiceResult::TYPE_EXISTS];
        }

        $user1ID = min($userID, $otherUserID);
        $user2ID = max($userID, $otherUserID);
        $conversation = $this->repository->createConversation(new ConversationCreateDTO(
            userID: $user1ID,
            otherUserID: $user2ID
        ));

        return ['type' => ServiceResult::TYPE_CREATED, 'conversation' => $conversation];
    }

    public function getConversationForViewer(int $userID, int $conversationID): array
    {
        $conversation = $this->conversationModel->newQuery()->find($conversationID);
        if (!$conversation) {
            return ['error' => ServiceResult::NOT_FOUND];
        }

        $user = $this->userModel->newQuery()->findOrFail($userID);
        if (!$user->hasAccessToConversation($conversationID)) {
            return ['error' => ServiceResult::FORBIDDEN];
        }

        return ['data' => $this->formatConversationSummary($conversationID, $userID)];
    }

    public function getOtherUserPhotos(int $userID, int $conversationID): array
    {
        $conversation = $this->conversationModel->newQuery()->find($conversationID);
        if (!$conversation) {
            return ['error' => ServiceResult::NOT_FOUND];
        }

        $user = $this->userModel->newQuery()->findOrFail($userID);
        if (!$user->hasAccessToConversation($conversationID)) {
            return ['error' => ServiceResult::FORBIDDEN];
        }

        $otherUser = $conversation->otherUser($userID);
        $photos = $otherUser->photos()
            ->orderBy('is_main', 'desc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => $photo->url,
                'is_main' => $photo->is_main,
            ]);

        return ['data' => $photos->values()->all()];
    }

    public function deleteConversation(int $userID, int $conversationID): ?string
    {
        $conversation = $this->conversationModel->newQuery()->find($conversationID);
        if (!$conversation) {
            return ServiceResult::NOT_FOUND;
        }

        $user = $this->userModel->newQuery()->findOrFail($userID);
        if (!$user->hasAccessToConversation($conversationID)) {
            return ServiceResult::FORBIDDEN;
        }

        $this->repository->softDeleteWithMessages($conversation);

        return null;
    }

    public function formatConversationSummary(int $conversationID, int $viewerUserID): array
    {
        $conversation = $this->conversationModel->newQuery()->findOrFail($conversationID);
        $conversation->load(['user1.mainPhoto', 'user2.mainPhoto']);
        $otherUser = $conversation->otherUser($viewerUserID);
        $otherUser->loadMissing('mainPhoto');

        return [
            'id' => $conversation->id,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'avatar_url' => $otherUser->mainPhoto?->url,
            ],
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
        ];
    }
}
