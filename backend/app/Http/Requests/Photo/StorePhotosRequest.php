<?php

namespace App\Http\Requests\Photo;

use App\Http\Controllers\PhotoController;
use Illuminate\Foundation\Http\FormRequest;

class StorePhotosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'photos' => 'required|array|min:1|max:' . PhotoController::MAX_PHOTOS,
            'photos.*' => 'required|file|mimes:jpeg,png,jpg|max:' . (PhotoController::MAX_FILE_SIZE / 1024),
        ];
    }

    public function messages(): array
    {
        return [
            'photos.required' => 'Не выбрано ни одного фото',
            'photos.min' => 'Нужно выбрать хотя бы одно фото',
            'photos.max' => 'Максимум 10 фото за раз',
            'photos.*.mimes' => 'Допустимые форматы: jpeg, png, jpg',
            'photos.*.max' => 'Максимальный размер файла: ' . (PhotoController::MAX_FILE_SIZE / 1024 / 1024) . 'MB',
        ];
    }
}
