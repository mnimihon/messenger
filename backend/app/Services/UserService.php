<?php

namespace App\Services;

interface UserService
{
    public function generateCode(): int;
    public function decrementAttempts(int $currentAttempts, int $possibleAttempts): int;
}
