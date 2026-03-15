<?php

namespace App\Services;

use App\Models\User;

interface UserService
{
    public function generateCode(): int;
    public function decrementAttempts(int $currentAttempts, int $possibleAttempts): int;
    public function resendAfter(User $user): int;
}
