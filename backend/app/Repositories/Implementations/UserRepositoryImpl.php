<?php
namespace App\Repositories\Implementations;

use App\DTO\UserCreateDTO;
use App\DTO\UserDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;

class UserRepositoryImpl implements UserRepository
{
    public function __construct(
        private readonly User $model,
    ) {}

    public function getByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    public function getAll($limit = 10): Collection
    {
        return $this->model->newQuery()->take($limit)->get();
    }

    public function create(UserCreateDTO $dto): User
    {
        return $this->model->newQuery()->create($dto->toDatabaseArray());
    }

    public function update(int $userID, UserDTO $dto): User
    {
        $user = $this->model->newQuery()->findOrFail($userID);
        $user->update($dto->toDatabaseArray());
        return $user;
    }

    public function delete(int $userID): bool
    {
        $user = $this->model->newQuery()->findOrFail($userID);

        return $user->delete();
    }
}
