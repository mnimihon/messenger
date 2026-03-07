<?php

namespace App\DTO;

class ConversationCreateDTO
{

    public function __construct(
        public readonly int $userID,
        public readonly int $otherUserID
    ) {}

    public function toDatabaseArray(): array
    {
        return [
            'conversation_id' => $this->userID,
            'sender_id' => $this->otherUserID,
        ];
    }
}
