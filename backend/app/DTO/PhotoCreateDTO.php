<?php

namespace App\DTO;

class PhotoCreateDTO
{

    public function __construct(
        public readonly int $userID,
        public readonly string $path,
        public readonly bool $is_approved,
        public readonly bool $is_main,
    ) {}

    public function toDatabaseArray(): array
    {
        return [
            'user_id' => $this->userID,
            'path' => $this->path,
            'is_approved' => $this->is_approved,
            'is_main' => $this->is_main
        ];
    }
}
