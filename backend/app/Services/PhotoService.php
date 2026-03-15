<?php
namespace App\Services;

use App\Models\UserPhoto;
use Illuminate\Http\UploadedFile;

interface PhotoService
{
    public function isPhotoLimit(int $userID, int $newCount): bool;
    public function delete(UserPhoto $photo, int $userID): bool;
    public function store(UploadedFile $file, int $userID): UserPhoto;
}
