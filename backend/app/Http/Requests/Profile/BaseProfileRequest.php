<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    abstract public function rules(): array;

    public function messages(): array
    {
        return [
            'name.required' => 'Имя обязательно для заполнения',
            'name.max' => 'Имя не может быть длиннее 255 символов',
            'current_password.required' => 'Текущий пароль обязателен',
            'new_password.required' => 'Новый пароль обязателен',
            'new_password.min' => 'Новый пароль должен содержать минимум 8 символов',
            'new_password.confirmed' => 'Пароли не совпадают',
            'password.required' => 'Пароль обязателен для подтверждения',
        ];
    }
}
