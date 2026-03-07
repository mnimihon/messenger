<?php
namespace App\Repositories;

use App\DTO\PhotoCreateDTO;
use App\Models\UserPhoto;
use Illuminate\Database\Eloquent\Collection;

interface PhotoRepository
{

    public function getAll(int $userID): Collection;
    public function delete(UserPhoto $user): bool;
    public function create(PhotoCreateDTO $dto): UserPhoto;
    public function setMain(UserPhoto $photo): bool;
}
