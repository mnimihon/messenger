<?php

namespace App\Services;

interface UserProfileService
{
    public function publicProfilePayload(int $userID): array;
    public function updateNameResult(int $userID, string $name): array;
    public function updatePasswordResult(int $userID, string $currentPlainPassword, string $newPlainPassword): array;
    public function deleteAccountResult(int $userID, string $plainPassword): array;
}
