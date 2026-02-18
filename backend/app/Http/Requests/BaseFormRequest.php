<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseFormRequest extends FormRequest
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

    public function attributes(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email',
            'password' => 'пароль',
            'code' => 'код подтверждения',
            'token' => 'токен',
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
