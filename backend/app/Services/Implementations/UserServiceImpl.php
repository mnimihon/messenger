<?php

namespace App\Services\Implementations;

use App\Models\User;
use App\Services\UserService;

class UserServiceImpl implements UserService
{

    public function decrementAttempts(int $currentAttempts, int $possibleAttempts): int
    {
        return $possibleAttempts - $currentAttempts;
    }

    public function generateCode(): int
    {
        return random_int(100000, 999999);
    }

    public function resendAfter(User $user): int
    {
        $secondsUntilExpires = (int) now()->diffInSeconds($user->verification_code_sent_expires_at);
        return max(0, $secondsUntilExpires);
    }
}
