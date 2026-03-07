<?php

namespace App\Services;

use App\DTO\UserCreateDTO;
use App\DTO\UserDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserService
{

    const SEND_COD_LATER_MINUTES = 1;
    const LOGIN_ATTEMPTS = 5;
    const RESET_PASSWORD_ATTEMPTS = 5;
    const RESET_PASSWORD_MINUTES = 1;
    const CAN_RESEND_AFTER = 60;

    public function create(UserCreateDTO $dto): User;
    public function update(User $user, UserDTO $dto): User;
    public function delete(User $user): bool;
    public function checkVerificationCode(User $user, int $code): bool;
    public function getAll(int $limit): Collection;
    public function getByEmail(string $email): ?User;
    public function generateCode(): int;
    public function trySendCodeLater(User $user): bool;
    public function setVerificationCode(User $user, int $verificationCode): bool;
    public function setResetPasswordCode(User $user, int $code): bool;
    public function isTooManyFailedLoginAttempts(User $user): bool;
    public function setCloseLogin(User $user): bool;
    public function setCloseResetPassword(User $user): bool;
    public function decrementAttempts(User $user, int $attempts): int;
    public function setZeroLoginAttempts(User $user): bool;
    public function isCodeAlreadyBeenSent(User $user): bool;
    public function isTooManyFailedResetAttempts(User $user): bool;
    public function setResetPasswordCodeNull(User $user): bool;
    public function setPassword(int $userID, string $password): bool;
    public function setPasswordNull(int $userID): bool;
    public function setName(int $userID, string $name): bool;
}
