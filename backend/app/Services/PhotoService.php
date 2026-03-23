<?php
namespace App\Services;

use App\Models\UserPhoto;
use Illuminate\Http\UploadedFile;

interface PhotoService
{
    public const MAX_PHOTOS = 10;

    public const MAX_FILE_SIZE_BYTES = 2 * 1024 * 1024;

    public function listPhotosForUser(int $userID): array;
    public function toArray(UserPhoto $photo): array;
    public function isPhotoLimit(int $userID, int $newCount): bool;
    public function delete(int $userPhotoID, int $userID): bool;
    public function store(UploadedFile $file, int $userID): UserPhoto;
    public function setMainForOwner(UserPhoto $photo, int $userID): ?string;
    public function deleteForOwner(UserPhoto $photo, int $userID): ?string;
    public function processUploads(array $files, int $userID): array;
}
