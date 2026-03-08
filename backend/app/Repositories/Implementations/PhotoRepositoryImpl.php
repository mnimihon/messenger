<?php
namespace App\Repositories\Implementations;

use App\DTO\PhotoCreateDTO;
use App\Models\Message;
use App\Models\User;
use App\Models\UserPhoto;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\PhotoRepository;

class PhotoRepositoryImpl implements PhotoRepository
{

    public function __construct(
        private readonly UserPhoto $model
    ) {}

    public function getAll(int $userID): Collection
    {
        $user = User::find($userID);
        return $user->photos()
            ->orderBy('is_main', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'is_approved' => $photo->is_approved,
                    'is_main' => $photo->is_main,
                    'created_at' => $photo->created_at,
                ];
            });
    }

    public function delete(UserPhoto $userPhoto): bool
    {
        return $userPhoto->delete();
    }

    public function create(PhotoCreateDTO $dto): UserPhoto
    {
        return $this->model->create($dto->toDatabaseArray());
    }

    public function setMain(UserPhoto $photo): bool
    {
        UserPhoto::where('user_id', $photo->user_id)
            ->update(['is_main' => false]);

        $photo->is_main = true;
        return $photo->save();
    }
}
