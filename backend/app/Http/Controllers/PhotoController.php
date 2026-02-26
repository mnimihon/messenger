<?php

namespace App\Http\Controllers;

use App\Http\Requests\Photo\StorePhotosRequest;
use App\Models\UserPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    const MAX_PHOTOS = 10;
    const MAX_FILE_SIZE = 2 * 1024 * 1024;

    public function index()
    {
        $user = Auth::user();

        $photos = $user->photos()
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

        return response()->json([
            'success' => true,
            'data' => $photos
        ]);
    }

    public function store(StorePhotosRequest $request)
    {
        $user = Auth::user();

        $currentCount = $user->photos()->count();
        $uploadedFiles = $request->file('photos');

        $uploadedPhotos = [];
        $errors = [];

        foreach ($uploadedFiles as $index => $file) {
            try {
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs(
                    'users/' . $user->id . '/photos',
                    $filename,
                    'public'
                );

                $photo = UserPhoto::create([
                    'user_id' => $user->id,
                    'path' => $path,
                    'is_approved' => true,
                    'is_main' => ($currentCount === 0 && $index === 0)
                ]);

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


    public function setMain(UserPhoto $photo)
    {
        $user = Auth::user();

        if ($photo->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа'
            ], 403);
        }

        UserPhoto::where('user_id', $user->id)
            ->update(['is_main' => false]);

        $photo->is_main = true;
        $photo->save();

        return response()->json([
            'success' => true,
            'message' => 'Фото установлено как главное'
        ]);
    }

    public function destroy(UserPhoto $photo)
    {
        $user = Auth::user();

        if ($photo->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа'
            ], 403);
        }

        $wasMain = $photo->is_main;

        if ($wasMain) {
            $photo->is_main = false;
            $photo->save();
        }

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        if ($wasMain) {
            $newMainPhoto = UserPhoto::where('user_id', $user->id)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($newMainPhoto) {
                $newMainPhoto->is_main = true;
                $newMainPhoto->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Фото удалено'
        ]);
    }
}
