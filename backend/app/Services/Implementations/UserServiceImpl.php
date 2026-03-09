<?php
namespace App\Services\Implementations;

use App\Services\UserService;

class UserServiceImpl implements UserService {

    public function decrementAttempts(int $currentAttempts, int $possibleAttempts): int
    {
        return $possibleAttempts - $currentAttempts;
    }

    public function generateCode(): int
    {
        return random_int(100000, 999999);
    }
}
