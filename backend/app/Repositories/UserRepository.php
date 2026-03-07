<?php
namespace App\Repositories;

use App\DTO\UserCreateDTO;
use App\DTO\UserDTO;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepository
{
    const VERIFICATION_CODE_EXPIRES_AT = 10;
    const LOGIN_LOCKED_UNTIL = 15;
    const RESET_PASSWORD_EXPIRES_AT = 10;
    const RESET_PASSWORD_LOCKED_MINUTES = 15;

    public function getByID(int $id): ?User;
    public function getByEmail(string $email): ?User;
    public function getAll($limit = 10): Collection;
    public function create(UserCreateDTO $dto): User;
    public function update(User $user, UserDTO $dto): User;
    public function delete(User $user): bool;
    public function setVerifiedEmail(User $user): bool;
    public function setCloseLogin(User $user): bool;
    public function setCloseResetPassword(User $user): bool;
    public function setVerificationCode(User $user, int $verificationCode): bool;
    public function setResetPasswordCode(User $user, int $code): bool;
    public function setZeroLoginAttempts(User $user): bool;
    public function setResetPasswordCodeNull(User $user): bool;
    public function setPassword(int $userID, string $password): bool;
    public function setName(int $userID, string $name): bool;
    public function setPasswordNull(int $userID): bool;
}
