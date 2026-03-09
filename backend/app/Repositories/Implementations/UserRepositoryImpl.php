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
}
