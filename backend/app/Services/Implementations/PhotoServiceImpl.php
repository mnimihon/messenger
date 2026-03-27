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
use RuntimeException;

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
        $filename = uniqid('', true) . '.jpg';
        $jpegBinary = $this->normalizeToJpeg($file);
        $path = 'users/' . $userID . '/photos/' . $filename;

        Storage::disk('public')->put($path, $jpegBinary);

        $user = $this->userModel->newQuery()->find($userID);
        $currentCount = $user->photos()->count();

        return $this->repository->create(new PhotoCreateDTO(
            userID: $userID,
            path: $path,
            is_approved: true,
            is_main: $currentCount == 0
        ));
    }

    private function normalizeToJpeg(UploadedFile $file): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            throw new RuntimeException('На сервере недоступна библиотека GD для обработки изображений');
        }

        $rawContent = file_get_contents($file->getRealPath());
        if ($rawContent === false || $rawContent === '') {
            throw new RuntimeException('Не удалось прочитать файл');
        }

        $imageInfo = getimagesizefromstring($rawContent);
        if ($imageInfo === false) {
            throw new RuntimeException('Файл не является валидным изображением');
        }

        $image = imagecreatefromstring($rawContent);
        if ($image === false) {
            throw new RuntimeException('Не удалось декодировать изображение');
        }

        try {
            // Re-encode strips metadata and rebuilds bytes into a clean JPEG stream.
            $jpegBinary = $this->encodeJpegToTargetSize($image);
        } finally {
            imagedestroy($image);
        }

        return $jpegBinary;
    }

    private function encodeJpegToTargetSize(\GdImage $image): string
    {
        $jpegBinary = $this->encodeJpeg($image, 90);
        if (strlen($jpegBinary) <= PhotoService::TARGET_FILE_SIZE_BYTES) {
            return $jpegBinary;
        }

        $qualities = [85, 80, 75, 70, 65, 60, 55, 50, 45, 40, 35, 30, 25];
        foreach ($qualities as $quality) {
            $jpegBinary = $this->encodeJpeg($image, $quality);
            if (strlen($jpegBinary) <= PhotoService::TARGET_FILE_SIZE_BYTES) {
                return $jpegBinary;
            }
        }

        throw new RuntimeException('Не удалось сжать изображение до 5MB без изменения размеров');
    }

    private function encodeJpeg(\GdImage $image, int $quality): string
    {
        ob_start();
        $encoded = imagejpeg($image, null, $quality);
        $binary = ob_get_clean();

        if ($encoded !== true || ! is_string($binary)) {
            throw new RuntimeException('Не удалось перекодировать изображение в JPEG');
        }

        return $binary;
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
            } catch (\Throwable $e) {
                $errors[] = 'Файл '.$file->getClientOriginalName().' не удалось загрузить: '.$e->getMessage();
            }
        }

        if (count($uploadedPhotos) === 0 && count($errors) > 0) {
            return [
                'http_status' => 422,
                'json' => [
                    'success' => false,
                    'message' => 'Не удалось загрузить ни одного фото',
                    'errors' => $errors,
                ],
            ];
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
