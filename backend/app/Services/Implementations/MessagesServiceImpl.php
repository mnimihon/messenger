<?php
namespace App\Services\Implementations;

use App\DTO\MessageDTO;
use App\Http\Responses\ServiceResult;
use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use App\Repositories\MessagesRepository;
use App\Services\MessagesService;

class MessagesServiceImpl implements MessagesService {

    public function __construct(
        private readonly MessagesRepository $repository,
        private readonly User $userModel,
    ) {}

    public function loadMessagesPage(int $userID, int $conversationID, int $cursor): array
    {
        $user = $this->userModel->newQuery()->findOrFail($userID);
        if (!$user->hasAccessToConversation($conversationID)) {
            return ['error' => ServiceResult::FORBIDDEN];
        }

        $this->repository->setIsReadAll($userID, $conversationID);

        return ['data' => $this->buildConversationPage($conversationID, $cursor)];
    }

    public function sendMessage(int $userID, int $conversationID, string $text): array
    {
        $user = $this->userModel->newQuery()->findOrFail($userID);
        if (!$user->hasAccessToConversation($conversationID)) {
            return ['error' => ServiceResult::FORBIDDEN];
        }

        $message = $this->repository->create(
            new MessageDTO(
                conversationID: $conversationID,
                senderID: $userID,
                message: $text,
                isRead: false,
                createdAt: new \DateTime(),
            )
        );
        $message->load('sender:id,name');

        event(new MessageSent($message));

        return ['message' => $message];
    }

    public function markAsRead(int $userID, array $messageIDs): int
    {
        return $this->repository->markAsRead($userID, $messageIDs);
    }

    public function deleteMessage(int $userID, int $messageID): ?string
    {
        $message = $this->repository->find($messageID);
        if (!$message) {
            return ServiceResult::NOT_FOUND;
        }
        if ($message->sender_id !== $userID) {
            return ServiceResult::FORBIDDEN;
        }

        $this->repository->delete($messageID);

        return null;
    }

    public function messageToArray(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender' => [
                'id' => $message->sender->id,
                'name' => $message->sender->name,
            ],
            'message' => $message->message,
            'is_read' => $message->is_read,
            'created_at' => $message->created_at,
        ];
    }

    private function buildConversationPage(int $conversationID, int $cursor): array
    {
        $messages = $this->repository->getByConversation($conversationID, $cursor);
        $nextCursor = $messages->isNotEmpty() ? $messages->last()->id : null;

        return [
            'messages' => $messages,
            'next_cursor' => $nextCursor,
        ];
    }
}
