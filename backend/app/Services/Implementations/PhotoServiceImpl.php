<?php
namespace App\Services\Implementations;

use App\DTO\PhotoCreateDTO;
use App\Models\User;
use App\Models\UserPhoto;
use App\Repositories\PhotoRepository;
use App\Services\PhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PhotoServiceImpl implements PhotoService
{
    public const MAX_PHOTOS = 10;

    public function __construct(
        private readonly PhotoRepository $repository
    ) {}

    public function isPhotoLimit(int $userID, int $newCount): bool
    {
        $user = User::find($userID);
        $currentCount = $user->photos()->count();
        return $currentCount + $newCount > self::MAX_PHOTOS;
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
