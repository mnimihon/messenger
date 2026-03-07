<?php
namespace App\Services\Implementations;

use App\DTO\UserCreateDTO;
use App\DTO\UserDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserServiceImpl implements UserService {

    public function __construct(
        private readonly UserRepository $repository
    ) {}

    public function create(UserCreateDTO $dto): User
    {
        return $this->repository->create($dto);
    }

    public function update(User $user, UserDTO $dto): User
    {
        return $this->repository->update($user, $dto);
    }

    public function delete(User $user): bool
    {
        return $this->repository->delete($user);
    }

    public function getAll(int $limit = 10): Collection
    {
        return $this->repository->getAll($limit);
    }

    public function getByEmail(string $email): User
    {
        return $this->repository->getByEmail($email);
    }

    public function checkVerificationCode(User $user, int $code): bool
    {
        return $user->verification_code != $code ||
            $user->verification_code_expires_at < now();
    }

    public function setVerifiedEmail(User $user): bool
    {
        return $this->repository->setVerifiedEmail($user);
    }

    public function setVerificationCode(User $user, int $verificationCode): bool
    {
        return $this->repository->setVerificationCode($user, $verificationCode);
    }

    public function setResetPasswordCode(User $user, int $code): bool {
        return $this->repository->setResetPasswordCode($user, $code);
    }

    public function trySendCodeLater(User $user): bool
    {
        return $user->verification_code_expires_at &&
            round($user->verification_code_expires_at->diffInMinutes(now())) < self::SEND_COD_LATER_MINUTES;
    }

    public function isTooManyFailedLoginAttempts(User $user): bool
    {
        return $user->login_locked_until && $user->login_locked_until > now();
    }

    public function setCloseLogin(User $user): bool
    {
        return $this->repository->setCloseLogin($user);
    }

    public function setCloseResetPassword(User $user): bool
    {
        return $this->repository->setCloseResetPassword($user);
    }

    public function setZeroLoginAttempts(User $user): bool
    {
        return $this->repository->setZeroLoginAttempts($user);
    }

    public function decrementAttempts(User $user, int $attempts): int
    {
        return $attempts - $user->login_attempts;
    }

    public function isCodeAlreadyBeenSent(User $user): bool
    {
        return $user->reset_password_code_sent_at &&
            $user->reset_password_code_sent_at->diffInMinutes(now()) < self::RESET_PASSWORD_MINUTES;
    }

    public function isTooManyFailedResetAttempts(User $user): bool
    {
        return $user->reset_password_locked_until && $user->reset_password_locked_until > now();
    }

    public function setResetPasswordCodeNull(User $user): bool
    {
        return $this->repository->setResetPasswordCodeNull($user);
    }

    public function setName(int $userID, string $name): bool
    {
        return $this->repository->setName($userID, $name);
    }

    public function setPassword(int $userID, string $password): bool
    {
        return $this->repository->setPassword($userID, $password);
    }

    public function setPasswordNull(int $userID): bool
    {
        return $this->repository->setPasswordNull($userID);
    }

    public function generateCode(): int
    {
        return random_int(100000, 999999);
    }
}
