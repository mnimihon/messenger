<?php
namespace App\Repositories;

use App\DTO\UserCreateDTO;
use App\DTO\UserDTO;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepository
{

    public function getByEmail(string $email): ?User;
    public function getAll($limit = 10): Collection;
    public function create(UserCreateDTO $dto): User;
    public function update(User $user, UserDTO $dto): User;
    public function delete(User $user): bool;
}
