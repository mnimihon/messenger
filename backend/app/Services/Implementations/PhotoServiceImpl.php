<?php
namespace App\Services\Implementations;

use App\DTO\PhotoCreateDTO;
use App\Http\Responses\ServiceResult;
use App\Models\User;
use App\Models\UserPhoto;
use App\Repositories\PhotoRepository;
use App\Services\PhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PhotoServiceImpl implements PhotoService
{
    public function __construct(
        private readonly PhotoRepository $repository,
        private readonly User $userModel,
        private readonly UserPhoto $userPhotoModel,
    ) {}

    public function listPhotosForUser(int $userID): array
    {
        return $this->repository->getAllForUser($userID)
            ->map(fn (UserPhoto $photo) => $this->toArray($photo))
            ->values()
            ->all();
    }

    public function toArray(UserPhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'url' => $photo->url,
            'is_approved' => $photo->is_approved,
            'is_main' => $photo->is_main,
            'created_at' => $photo->created_at,
        ];
    }

    public function isPhotoLimit(int $userID, int $newCount): bool
    {
        $user = $this->userModel->newQuery()->find($userID);
        $currentCount = $user->photos()->count();
        return $currentCount + $newCount > PhotoService::MAX_PHOTOS;
    }

    public function store(UploadedFile $file, int $userID): UserPhoto
    {
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs(
            'users/' . $userID . '/photos',
            $filename,
            'public'
        );

        $user = $this->userModel->newQuery()->find($userID);
        $currentCount = $user->photos()->count();

        return $this->repository->create(new PhotoCreateDTO(
            userID: $userID,
            path: $path,
            is_approved: true,
            is_main: $currentCount == 0
        ));
    }

    public function delete(int $userPhotoID, int $userID): bool
    {
        $photo = $this->userPhotoModel->newQuery()->findOrFail($userPhotoID);
        $wasMain = $photo->is_main;

        Storage::disk('public')->delete($photo->path);
        $this->repository->delete($userPhotoID);

        if ($wasMain) {
            $newMainPhoto = $this->userPhotoModel->newQuery()
                ->where('user_id', $userID)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($newMainPhoto) {
                $this->repository->setMain($newMainPhoto->id);
            }
        }

        return true;
    }

    public function setMainForOwner(UserPhoto $photo, int $userID): ?string
    {
        if ($photo->user_id !== $userID) {
            return ServiceResult::FORBIDDEN;
        }
        $this->repository->setMain($photo->id);

        return null;
    }

    public function deleteForOwner(UserPhoto $photo, int $userID): ?string
    {
        if ($photo->user_id !== $userID) {
            return ServiceResult::FORBIDDEN;
        }
        $this->delete($photo->id, $userID);

        return null;
    }

    public function processUploads(array $files, int $userID): array
    {
        if ($this->isPhotoLimit($userID, count($files))) {
            return [
                'http_status' => 422,
                'json' => [
                    'success' => false,
                    'message' => 'Достигнут лимит: максимум '.PhotoService::MAX_PHOTOS.' фотографий.',
                ],
            ];
        }

        $uploadedPhotos = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                $photo = $this->store($file, $userID);
                $uploadedPhotos[] = $this->toArray($photo);
            } catch (\Exception $e) {
                $errors[] = 'Файл '.$file->getClientOriginalName().' не удалось загрузить: '.$e->getMessage();
            }
        }

        $user = $this->userModel->newQuery()->findOrFail($userID);
        $message = 'Загружено фото: '.count($uploadedPhotos);

        return [
            'http_status' => 201,
            'json' => [
                'success' => true,
                'message' => $message,
                'data' => [
                    'uploaded' => $uploadedPhotos,
                    'errors' => $errors,
                    'total_photos' => $user->photos()->count(),
                ],
            ],
        ];
    }
}
