<?php
namespace App\Repositories;

use App\DTO\PhotoCreateDTO;
use App\Models\UserPhoto;
use Illuminate\Database\Eloquent\Collection;

interface PhotoRepository
{
    public function getAllForUser(int $userID): Collection;
    public function delete(int $userPhotoID): bool;
    public function create(PhotoCreateDTO $dto): UserPhoto;
    public function setMain(int $userPhotoID): bool;
}
