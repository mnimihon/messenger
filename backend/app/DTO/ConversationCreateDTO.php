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
            'user1_id' => $this->userID,
            'user2_id' => $this->otherUserID,
        ];
    }
}
