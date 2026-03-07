<?php
namespace App\Services;

use App\Models\UserPhoto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface PhotoService
{

    public function getAll(int $userID): Collection;
    public function delete(UserPhoto $photo, int $userID): bool;
    public function store(UploadedFile $file, int $userID): UserPhoto;
    public function setMain(UserPhoto $photo): bool;
}
