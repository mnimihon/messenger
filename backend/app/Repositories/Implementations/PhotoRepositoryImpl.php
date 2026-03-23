<?php
namespace App\Repositories\Implementations;

use App\DTO\PhotoCreateDTO;
use App\Models\UserPhoto;
use App\Repositories\PhotoRepository;
use Illuminate\Database\Eloquent\Collection;

class PhotoRepositoryImpl implements PhotoRepository
{
    public function __construct(
        private readonly UserPhoto $model,
    ) {}

    public function getAllForUser(int $userID): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userID)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function delete(int $userPhotoID): bool
    {
        $userPhoto = $this->model->newQuery()->findOrFail($userPhotoID);

        return $userPhoto->delete();
    }

    public function create(PhotoCreateDTO $dto): UserPhoto
    {
        return $this->model->newQuery()->create($dto->toDatabaseArray());
    }

    public function setMain(int $userPhotoID): bool
    {
        $photo = $this->model->newQuery()->findOrFail($userPhotoID);

        $this->model->newQuery()
            ->where('user_id', $photo->user_id)
            ->update(['is_main' => false]);

        $photo->is_main = true;
        return $photo->save();
    }
}
