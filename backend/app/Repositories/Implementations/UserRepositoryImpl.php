<?php
namespace App\Repositories\Implementations;

use App\DTO\UserCreateDTO;
use App\DTO\UserDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserRepositoryImpl implements UserRepository {

    public function __construct(
        private readonly User $model
    ) {}

    public function getByID(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function getByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function getAll($limit = 10): Collection
    {
        return $this->model->take($limit)->get();
    }

    public function create(UserCreateDTO $dto): User
    {
        return $this->model->create($dto->toDatabaseArray());
    }

    public function update(User $user, UserDTO $dto): User
    {
        $user->update($dto->toDatabaseArray());
        return $user;
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function setVerifiedEmail(User $user): bool
    {
        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->verification_code_expires_at = null;
        return $user->save();
    }

    public function setVerificationCode(User $user, int $verificationCode): bool
    {
        $user->verification_code = $verificationCode;
        $user->verification_code_expires_at = now()->addMinutes(self::VERIFICATION_CODE_EXPIRES_AT);
        return $user->save();
    }

    public function setResetPasswordCode(User $user, int $code): bool
    {
        $user->reset_password_code = $code;
        $user->reset_password_code_expires_at = now()->addMinutes(self::RESET_PASSWORD_EXPIRES_AT);
        $user->reset_password_code_sent_at = now();

        $user->reset_password_attempts = 0;
        $user->reset_password_locked_until = null;

        return $user->save();
    }

    public function setPassword(int $userID, string $password): bool
    {
        $user = User::find($userID);
        $user->password = Hash::make($password);
        return $user->save();
    }

    public function setName(int $userID, string $name): bool
    {
        $user = User::find($userID);
        $user->name = $name;
        return $user->save();
    }

    public function setPasswordNull(int $userID): bool
    {
        $user = User::find($userID);
        $user->reset_password_code = null;
        $user->reset_password_code_expires_at = null;
        return $user->save();
    }

    public function setCloseLogin(User $user): bool
    {
        $user->login_locked_until = now()->addMinutes(self::LOGIN_LOCKED_UNTIL);
        $user->login_attempts = 0;
        return $user->save();
    }

    public function setCloseResetPassword(User $user): bool
    {
        $user->reset_password_locked_until = now()->addMinutes(self::RESET_PASSWORD_LOCKED_MINUTES);
        $user->save();
        return $user->save();
    }

    public function setZeroLoginAttempts(User $user): bool
    {
        $user->login_attempts = 0;
        return $user->save();
    }

    public function setResetPasswordCodeNull(User $user): bool
    {
        $user->reset_password_code = null;
        $user->reset_password_code_expires_at = null;
        return $user->save();
    }
}
