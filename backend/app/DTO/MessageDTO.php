<?php

namespace App\DTO;

class MessageDTO
{
    public function __construct(
        public readonly int $conversationID,
        public readonly int $senderID,
        public readonly string $message,
        public readonly bool $isRead,
        public readonly \DateTime $createdAt,
        public readonly int $id = 0
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            conversationID: $data['conversation_id'],
            senderID: $data['sender_id'],
            message: $data['message'],
            isRead: (bool) $data['is_read'],
            createdAt: new \DateTime($data['created_at']),
            id: $data['id']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversationID,
            'sender_id' => $this->senderID,
            'message' => $this->message,
            'is_read' => $this->isRead,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    public function toDatabaseArray(): array
    {
        return [
            'conversation_id' => $this->conversationID,
            'sender_id' => $this->senderID,
            'message' => $this->message,
            'is_read' => $this->isRead,
        ];
    }
}
