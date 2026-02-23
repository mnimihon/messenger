<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    abstract public function rules(): array;

    public function messages(): array
    {
        return [
            'required' => 'Поле :attribute обязательно для заполнения',
            'email' => 'Введите корректный email адрес',
            'unique' => 'Такой :attribute уже существует',
            'min' => 'Поле :attribute должно содержать минимум :min символов',
            'confirmed' => 'Пароли не совпадают',
            'max' => 'Поле :attribute должно содержать максимум :max символов',
            'string' => 'Поле :attribute должно быть строкой',
            'exists' => 'Выбранный :attribute не найден',
            'size' => 'Поле :attribute должно содержать :size символов',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422)
        );
    }
}
