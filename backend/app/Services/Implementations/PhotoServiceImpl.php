<?php
namespace App\Services\Implementations;

use App\DTO\PhotoCreateDTO;
use App\DTO\UserCreateDTO;
use App\Models\User;
use App\Models\UserPhoto;
use App\Repositories\PhotoRepository;
use App\Services\PhotoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PhotoServiceImpl implements PhotoService {

    public function __construct(
        private readonly PhotoRepository $repository
    ) {}

    public function getAll(int $userID): Collection
    {
        return $this->repository->getAll($userID);
    }

    public function setMain(UserPhoto $photo): bool
    {
        return $this->repository->setMain($photo);
    }

    public function store(UploadedFile $file, int $userID): UserPhoto
    {
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs(
            'users/' . $userID . '/photos',
            $filename,
            'public'
        );

        $user = User::find($userID);
        $currentCount = $user->photos()->count();

        return $this->repository->create(new PhotoCreateDTO(
            userID: $userID,
            path: $path,
            is_approved: true,
            is_main: $currentCount == 0
        ));
    }

    public function delete(UserPhoto $photo, int $userID): bool
    {
        $wasMain = $photo->is_main;

        Storage::disk('public')->delete($photo->path);
        $this->repository->delete($photo);

        if ($wasMain) {
            $newMainPhoto = UserPhoto::where('user_id', $userID)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($newMainPhoto) {
                $this->repository->setMain($newMainPhoto);
            }
        }

        return true;
    }
}
