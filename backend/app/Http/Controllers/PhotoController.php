<?php

namespace App\Http\Controllers;

use App\Http\Requests\Photo\StorePhotosRequest;
use App\Http\Responses\ServiceResult;
use App\Models\UserPhoto;
use App\Services\PhotoService;
use Illuminate\Support\Facades\Auth;

class PhotoController extends Controller
{
    public function index(PhotoService $photoService)
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => $photoService->listPhotosForUser($user->id),
        ]);
    }

    public function store(StorePhotosRequest $request, PhotoService $photoService)
    {
        $user = Auth::user();

        return $this->jsonFromService($photoService->processUploads(
            $request->file('photos'),
            $user->id
        ));
    }

    public function setMain(UserPhoto $photo, PhotoService $photoService)
    {
        $user = Auth::user();
        $error = $photoService->setMainForOwner($photo, $user->id);
        if ($error === ServiceResult::FORBIDDEN) {
            return $this->jsonError(ServiceResult::FORBIDDEN, 'Нет доступа');
        }

        return response()->json([
            'success' => true,
            'message' => 'Фото установлено как главное',
        ]);
    }

    public function destroy(UserPhoto $photo, PhotoService $photoService)
    {
        $user = Auth::user();
        $error = $photoService->deleteForOwner($photo, $user->id);
        if ($error === ServiceResult::FORBIDDEN) {
            return $this->jsonError(ServiceResult::FORBIDDEN, 'Нет доступа');
        }

        return response()->json([
            'success' => true,
            'message' => 'Фото удалено',
        ]);
    }
}
