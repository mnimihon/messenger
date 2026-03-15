<?php

namespace App\Http\Controllers;

use App\Http\Requests\Photo\StorePhotosRequest;
use App\Models\UserPhoto;
use App\Repositories\PhotoRepository;
use App\Services\PhotoService;
use Illuminate\Support\Facades\Auth;

class PhotoController extends Controller
{
    const MAX_PHOTOS = 10;
    const MAX_FILE_SIZE = 2 * 1024 * 1024;

    public function index(PhotoRepository $photoRepository)
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $photoRepository->getAll($user->id)
        ]);
    }

    public function store(StorePhotosRequest $request, PhotoService $photoService)
    {
        $user = Auth::user();
        $uploadedFiles = $request->file('photos');

        if ($photoService->isPhotoLimit($user->id, count($uploadedFiles))) {
            return response()->json([
                'success' => false,
                'message' => 'Достигнут лимит: максимум ' . self::MAX_PHOTOS . ' фотографий.'
            ], 422);
        }

        $uploadedPhotos = [];
        $errors = [];

        foreach ($uploadedFiles as $file) {
            try {
                $photo = $photoService->store($file, $user->id);

                $uploadedPhotos[] = [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'is_approved' => $photo->is_approved,
                    'is_main' => $photo->is_main,
                    'created_at' => $photo->created_at,
                ];

            } catch (\Exception $e) {
                $errors[] = "Файл {$file->getClientOriginalName()} не удалось загрузить: " . $e->getMessage();
            }
        }

        $message = 'Загружено фото: ' . count($uploadedPhotos);
        if (!empty($errors)) {
            $message .= ', ошибок: ' . count($errors);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'uploaded' => $uploadedPhotos,
                'errors' => $errors,
                'total_photos' => $user->photos()->count()
            ]
        ], 201);
    }


    public function setMain(UserPhoto $photo, PhotoRepository $photoRepository)
    {
        $user = Auth::user();

        if ($photo->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа'
            ], 403);
        }

        $photoRepository->setMain($photo);

        return response()->json([
            'success' => true,
            'message' => 'Фото установлено как главное'
        ]);
    }

    public function destroy(UserPhoto $photo, PhotoService $photoService)
    {
        $user = Auth::user();
        if ($photo->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа'
            ], 403);
        }
        $photoService->delete($photo, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Фото удалено'
        ]);
    }
}
