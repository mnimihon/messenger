<?php

namespace App\Services;

use App\Models\User;

interface AccountAuthService
{
    public function register(string $name, string $email, string $plainPassword): array;
    public function verificationCooldown(string $email): array;
    public function verifyEmail(string $email, int|string $code): array;
    public function resendVerificationCode(string $email): array;
    public function login(string $email, string $password): array;
    public function forgotPassword(string $email): array;
    public function resetPassword(string $email, int|string $code, string $newPlainPassword): array;
    public function revokeCurrentToken(User $user): void;
    public function authenticatedUserPayload(User $user): array;
}
