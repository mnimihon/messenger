<?php

namespace App\DTO;

use Carbon\CarbonInterface;

class UserCreateDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly int $verificationCode,
        public readonly CarbonInterface $verificationCodeExpiresAt,
    ) {}

    public function toDatabaseArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'verification_code' => $this->verificationCode,
            'verification_code_expires_at' => $this->verificationCodeExpiresAt
        ];
    }
}
