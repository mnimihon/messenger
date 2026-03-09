<?php
namespace App\Services\Implementations;

use App\Models\Conversation;
use App\Repositories\MessagesRepository;
use App\Services\MessagesService;

class MessagesServiceImpl implements MessagesService {

    public function __construct(
        private readonly MessagesRepository $repository
    ) {}

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
